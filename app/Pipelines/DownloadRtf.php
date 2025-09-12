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
        // Enhanced RTF to HTML conversion with proper line break preservation

        // Remove RTF header and font table
        $html = preg_replace('/^\{.*?\\\\rtf1[^}]*\}/', '', $rtfContent);

        // Convert paragraph breaks to line breaks (fix regex escaping)
        $html = preg_replace('/\\\\par\s*/', '<br>', $html);
        $html = preg_replace('/\\\\par\b/', '<br>', $html);

        // Add line breaks after periods followed by capital letters (natural paragraph breaks)
        $html = preg_replace('/([.!?])\s+([A-Z])/', '$1<br><br>$2', $html);

        // Convert bold formatting (fix regex escaping)
        $html = preg_replace('/\\\\b\s*/', '<strong>', $html);
        $html = preg_replace('/\\\\b0\s*/', '</strong>', $html);

        // Convert italic formatting (fix regex escaping)
        $html = preg_replace('/\\\\i\s*/', '<em>', $html);
        $html = preg_replace('/\\\\i0\s*/', '</em>', $html);

        // Convert underline formatting (fix regex escaping)
        $html = preg_replace('/\\\\ul\s*/', '<u>', $html);
        $html = preg_replace('/\\\\ul0\s*/', '</u>', $html);

        // Remove font control words (fix regex escaping)
        $html = preg_replace('/\\\\f\d+\s*/', '', $html);
        $html = preg_replace('/\\\\fs\d+\s*/', '', $html);
        $html = preg_replace('/\\\\cf\d+\s*/', '', $html);

        // Remove other RTF control words (fix regex escaping)
        $html = preg_replace('/\\\\[a-zA-Z]+\d*\s*/', '', $html);

        // Remove braces
        $html = preg_replace('/[{}]/', '', $html);

        // Clean up multiple spaces but preserve line breaks
        $html = preg_replace('/[ \t]+/', ' ', $html);

        // Clean up multiple line breaks
        $html = preg_replace('/(<br>\s*){3,}/', '<br><br>', $html);

        // Remove leading/trailing whitespace
        $html = trim($html);

        // Wrap in proper HTML structure if content exists
        if (! empty($html)) {
            $html = '<div class="enhanced-profile-text">'.$html.'</div>';
        }

        return $html;
    }
}
