<?php

namespace App\Pipelines;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DownloadPhoto
{
    public function __invoke($record, \Closure $next)
    {
        $record['enhanced_photo_path'] = $this->downloadAndProcessPhoto(
            $record['enhanced-photo-url'] ?? null,
            $record['enhanced-photo-filename'] ?? null,
            $record['type'] ?? null
        );
        unset($record['enhanced-photo-url']);

        return $next($record);
    }

    private function downloadAndProcessPhoto($url, $filename, $type)
    {
        if (! $filename || ! $type) {
            return null;
        }

        // Normalize filename - remove special characters, spaces, etc.
        $normalizedFilename = $this->normalizeFilename($filename);

        // If no URL provided but we have a filename, construct Google Drive URL
        if (! $url && $filename) {
            $url = $this->constructGoogleDriveUrl($filename);
        }

        if (! $url) {
            return null;
        }

        // Store in public storage for public access
        $fileName = "enhanced_photos/{$type}/{$normalizedFilename}";

        if (Storage::disk('public')->exists($fileName)) {
            return Storage::disk('public')->url($fileName);
        }

        try {
            $response = Http::timeout(30)
                ->withOptions(['allow_redirects' => true])
                ->get($url);
            $response->throw();

            // Check if the response is actually an image
            $contentType = $response->header('Content-Type');
            if (! str_contains($contentType, 'image/')) {
                throw new \Exception("Response is not an image, content-type: {$contentType}");
            }

            $imageContent = $response->body();

            Storage::disk('public')->put($fileName, $imageContent);

            return Storage::disk('public')->url($fileName);
        } catch (\Exception $e) {
            Log::warning('Photo download/conversion failed', [
                'error' => $e->getMessage(),
                'url' => $url,
                'filename' => $filename,
                'normalized_filename' => $normalizedFilename,
                'type' => $type,
            ]);

            return null;
        }
    }

    private function normalizeFilename($filename)
    {
        // Remove or replace special characters, spaces, etc.
        $normalized = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        $normalized = preg_replace('/_+/', '_', $normalized); // Replace multiple underscores with single
        $normalized = trim($normalized, '_'); // Remove leading/trailing underscores

        // Ensure it has a proper extension
        if (! preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $normalized)) {
            $normalized .= '.jpg'; // Default to jpg if no extension
        }

        return $normalized;
    }

    private function constructGoogleDriveUrl($filename)
    {
        // Google Drive direct download URL pattern based on the existing pattern in the codebase
        // Using the washingtonian.com domain pattern that was used before
        return 'https://drive.google.com/a/washingtonian.com/uc?id='.urlencode($filename).'&export=download';
    }
}
