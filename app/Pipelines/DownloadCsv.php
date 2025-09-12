<?php

namespace App\Pipelines;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DownloadCsv
{
    public function __invoke($payload, \Closure $next)
    {
        try {
            // Handle both old and new payload structures
            $csvUrl = $payload->csv_url ?? (isset($payload->remoteUrl) ? $payload->remoteUrl : null);
            $fileName = $payload->fileName ?? 'import_'.time().'_'.md5($csvUrl).'.csv';

            if (! $csvUrl) {
                throw new \Exception('No CSV URL provided in payload');
            }

            Log::info('📥 Downloading CSV', [
                'import_id' => $payload->import_id ?? 'unknown',
                'url' => $csvUrl,
                'filename' => $fileName,
            ]);

            $response = Http::timeout(30)->get($csvUrl);
            $response->throw();

            Storage::put($fileName, $response->body());

            // Add the fileName to the payload for the next pipeline step
            $payload->fileName = $fileName;
            $payload->remoteUrl = $csvUrl;

            Log::info('✅ CSV downloaded successfully', [
                'import_id' => $payload->import_id ?? 'unknown',
                'filename' => $fileName,
                'size_bytes' => strlen($response->body()),
                'size_mb' => round(strlen($response->body()) / 1024 / 1024, 2),
            ]);

        } catch (\Exception $e) {
            Log::error('CSV download failed', [
                'error' => $e->getMessage(),
                'url' => $csvUrl ?? 'unknown',
                'filename' => $fileName ?? 'unknown',
            ]);
            throw $e;
        }

        return $next($payload);
    }
}
