<?php

namespace App\Pipelines;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AddLocations
{
    const CACHE_DURATION = 1440;

    public function __invoke($record, \Closure $next)
    {
        $record['locations'] = $this->getFormattedGeolocations($record);

        return $next($record);
    }

    private function getFormattedGeolocations(array $record): array
    {
        return collect($this->getGeolocations($record))
            ->reject(fn ($location) => is_null($location['address']) && is_null($location['lat']) && is_null($location['lng']) && empty($location['phone']))
            ->map(fn ($location) => array_filter($location))
            ->values()
            ->toArray();
    }

    private function getGeolocations(array $record): array
    {
        return Cache::remember('geolocations_'.$record['id'], now()->addMinutes(self::CACHE_DURATION), function () use ($record) {
            return collect(['a', 'b', 'c', 'd'])
                ->mapWithKeys(function ($location) use ($record) {
                    $addressData = $this->buildAddress($record, $location);
                    $addressData['phone'] = $this->formatPhoneNumber($addressData['phone']);
                    if (! empty(trim($addressData['address']))) {
                        $geocoded = $this->getCachedGeocode($addressData['address']);

                        return [$location => array_merge($addressData, [
                            'lat' => $geocoded['lat'] ?? null,
                            'lng' => $geocoded['lng'] ?? null,
                        ])];
                    } else {
                        return [$location => [
                            'address' => null,
                            'phone' => $addressData['phone'],
                            'lat' => null,
                            'lng' => null,
                        ]];
                    }
                })
                ->toArray();
        });
    }

    private function getCachedGeocode(string $address): ?array
    {
        $cacheKey = 'geocode_'.md5($address);

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), fn () => $this->geocodeAddress($address));
    }

    private function buildAddress(array $record, string $location): array
    {
        $addressParts = ['street-1', 'street-2', 'city', 'state', 'zip', 'country'];
        $address = implode(', ', array_filter(array_map(fn ($part) => $record["{$location}-{$part}"] ?? '', $addressParts)));

        return [
            'address' => $address,
            'phone' => $record["{$location}-phone"] ?? '',
        ];
    }

    private function formatPhoneNumber(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 10) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6));
        } elseif (strlen($digits) === 11 && $digits[0] === '1') {
            return sprintf('+1 (%s) %s-%s', substr($digits, 1, 3), substr($digits, 4, 3), substr($digits, 7));
        }

        return $phone;
    }

    private function geocodeAddress(string $address): ?array
    {
        $address = trim(preg_replace('/\bSuite\s*200\b/i', '', $address));
        $address = rtrim($address, ', ');

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Wagstaffe/1.0 (wagstaffe@gmail.com)',
            ])->get('https://nominatim.openstreetmap.org/search', [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
            ])->throw();

            $data = $response->json();
            if (! empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
                return [
                    'lat' => round((float) $data[0]['lat'], 6),
                    'lng' => round((float) $data[0]['lon'], 6),
                ];
            }
        } catch (\Exception $e) {
            Log::warning('OpenStreetMap geocoding failed. Address: '.$address, ['error' => $e->getMessage()]);
        }

        return null;
    }
}
