<?php

namespace App\Pipelines;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodeAddress
{
    public function __invoke($record, \Closure $next)
    {
        // Check if we already have coordinates
        if (isset($record['latitude']) && isset($record['longitude'])) {
            return $next($record);
        }

        // Build the full address
        $address = $this->buildAddress($record);

        if (! $address) {
            return $next($record);
        }

        // Geocode the address
        $coordinates = $this->geocodeAddress($address);

        if ($coordinates) {
            $record['latitude'] = $coordinates['lat'];
            $record['longitude'] = $coordinates['lng'];
            $record['geocoded_address'] = $address;

            Log::info('Address geocoded successfully', [
                'address' => $address,
                'coordinates' => $coordinates,
                'provider_id' => $record['id'] ?? $record['provider_id'] ?? 'unknown',
            ]);
        } else {
            Log::warning('Address geocoding failed', [
                'address' => $address,
                'provider_id' => $record['id'] ?? $record['provider_id'] ?? 'unknown',
            ]);
        }

        return $next($record);
    }

    private function buildAddress($record)
    {
        $street = $record['address-street'] ?? $record['address_street'] ?? '';
        $city = $record['address-city'] ?? $record['address_city'] ?? '';
        $state = $record['address-state'] ?? $record['address_state'] ?? '';
        $zip = $record['address-zip'] ?? $record['address_zip'] ?? '';

        if (empty($street) || empty($city) || empty($state)) {
            return null;
        }

        // Simplify address format for better geocoding results
        $address = trim($street).' '.trim($city).' '.trim($state);

        // Add zip if available
        if (! empty($zip)) {
            $address .= ' '.trim($zip);
        }

        // Clean up address (remove commas, extra spaces)
        $address = preg_replace('/,/', '', $address);
        $address = preg_replace('/\s+/', ' ', $address);
        $address = trim($address);

        return $address;
    }

    private function geocodeAddress($address)
    {
        try {
            // Use OpenStreetMap Nominatim (free, no API key required)
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'WashFinders/1.0 (Healthcare Provider Directory)',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'us', // Focus on US addresses
                    'addressdetails' => 1,
                ]);

            if (! $response->successful()) {
                Log::warning('Geocoding API request failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            if (empty($data)) {
                Log::warning('Geocoding API returned no results', [
                    'address' => $address,
                ]);

                return null;
            }

            $result = $data[0];

            return [
                'lat' => (float) $result['lat'],
                'lng' => (float) $result['lon'],
            ];

        } catch (\Exception $e) {
            Log::error('Geocoding failed with exception', [
                'error' => $e->getMessage(),
                'address' => $address,
            ]);

            return null;
        }
    }
}
