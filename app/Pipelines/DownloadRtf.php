<?php

namespace App\Pipelines;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DownloadRtf
{
    public function __invoke($record, \Closure $next)
    {
        $record['enhanced_profile_text_path'] = $this->downloadAndProcessRtf(
            $record['enhanced-profile-text-url'] ?? null,
            $record['enhanced-profile-text-filename'] ?? null,
            $record['type'] ?? null
        );

        // Remove the URL field after processing
        unset($record['enhanced-profile-text-url']);

        return $next($record);
    }

    private function downloadAndProcessRtf($url, $filename, $type)
    {
        if (! $filename || ! $type) {
            return null;
        }

        $normalizedFilename = $this->normalizeFilename($filename);

        // If no URL provided but filename exists, construct Google Drive URL
        if (! $url && $filename) {
            $url = $this->constructGoogleDriveUrl($filename);
        }

        if (! $url) {
            return null;
        }

        $fileName = "enhanced_texts/{$type}/{$normalizedFilename}";

        // Check if file already exists
        if (Storage::disk('public')->exists($fileName)) {
            return Storage::disk('public')->url($fileName);
        }

        try {
            $response = Http::timeout(30)
                ->withOptions(['allow_redirects' => true])
                ->get($url);
            $response->throw();

            $contentType = $response->header('Content-Type');

            // Accept RTF and text content types
            if (! str_contains($contentType, 'text/') &&
                ! str_contains($contentType, 'application/rtf') &&
                ! str_contains($contentType, 'application/octet-stream')) {
                throw new \Exception("Response is not a text file, content-type: {$contentType}");
            }

            $rtfContent = $response->body();

            // Store the RTF file
            Storage::disk('public')->put($fileName, $rtfContent);

            // Convert RTF to HTML for display
            $htmlContent = $this->convertRtfToHtml($rtfContent);

            // Store the converted HTML content in the record
            $record['enhanced-profile-text'] = $htmlContent;

            // Store the RTF file path
            $record['enhanced_profile_text_path'] = Storage::disk('public')->url($fileName);

            return Storage::disk('public')->url($fileName);
        } catch (\Exception $e) {
            Log::warning('RTF download/conversion failed', [
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
        $normalized = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        $normalized = preg_replace('/_+/', '_', $normalized);
        $normalized = trim($normalized, '_');

        if (! preg_match('/\.(rtf|txt)$/i', $normalized)) {
            $normalized .= '.rtf';
        }

        return $normalized;
    }

    private function constructGoogleDriveUrl($filename)
    {
        return 'https://drive.google.com/a/washingtonian.com/uc?id='.urlencode($filename).'&export=download';
    }

    private function convertRtfToHtml($rtfContent)
    {
        // Simple RTF to HTML conversion
        // Remove RTF control words and convert basic formatting

        // Remove RTF header
        $html = preg_replace('/^\{.*?\\rtf1/', '', $rtfContent);

        // Convert basic formatting
        $html = preg_replace('/\\b(\\w+)\\b/', '$1', $html);
        $html = preg_replace('/\\par/', '<br>', $html);
        $html = preg_replace('/\\b/', '', $html);
        $html = preg_replace('/\\f\d+/', '', $html);
        $html = preg_replace('/\\fs\d+/', '', $html);

        // Clean up remaining RTF control words
        $html = preg_replace('/\\[a-zA-Z]+\d*\s?/', '', $html);
        $html = preg_replace('/[{}]/', '', $html);

        // Clean up extra whitespace
        $html = preg_replace('/\s+/', ' ', $html);
        $html = trim($html);

        // Wrap in basic HTML structure if content exists
        if (! empty($html)) {
            $html = '<div class="enhanced-profile-text">'.$html.'</div>';
        }

        return $html;
    }
}
