<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\Provider;
use App\Pipelines\AddLocations;
use App\Pipelines\DownloadCsv;
use App\Pipelines\GeocodeAddress;
use App\Pipelines\ProcessCsv;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Log;

class ProcessSingleProviderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $providerId,
        public int $providerDbId,
        public string $providerType
    ) {
        $this->onQueue('imports');
    }

    public function handle(): void
    {
        $startTime = now();

        Log::info('🔄 Single provider import job started', [
            'provider_id' => $this->providerId,
            'provider_type' => $this->providerType,
            'provider_db_id' => $this->providerDbId,
            'started_at' => $startTime->toDateTimeString(),
            'job_id' => $this->job->getJobId(),
        ]);

        try {
            // Get the import configuration for this provider type
            $import = Import::where('provider_type', $this->providerType)->first();

            if (! $import) {
                Log::error('❌ No import configuration found for provider type', [
                    'provider_id' => $this->providerId,
                    'provider_type' => $this->providerType,
                    'provider_db_id' => $this->providerDbId,
                ]);

                return;
            }

            // Get the provider record to log details
            $provider = Provider::find($this->providerDbId);
            $providerTitle = $provider ? ($provider->meta['title'] ?? 'Unknown') : 'Unknown';

            Log::info('🎯 Processing single provider', [
                'provider_id' => $this->providerId,
                'provider_type' => $this->providerType,
                'provider_title' => $providerTitle,
                'provider_db_id' => $this->providerDbId,
                'import_id' => $import->id,
                'csv_url' => $import->csv_url,
            ]);

            // Create a pipeline payload for single provider processing
            $payload = (object) [
                'type' => $this->providerType,
                'csv_url' => $import->csv_url,
                'import_settings' => $import->import_settings ?? [],
                'mapping_config' => $import->mapping_config ?? [],
                'import_id' => $import->id,
                'single_provider_id' => $this->providerId, // Flag to process only this provider
            ];

            // Run the pipeline
            $result = app(Pipeline::class)
                ->send($payload)
                ->through([
                    DownloadCsv::class,
                    ProcessCsv::class,
                    AddLocations::class,
                    GeocodeAddress::class,
                ])
                ->thenReturn();

            $endTime = now();
            $duration = $startTime->diffInSeconds($endTime);

            Log::info('✅ Single dentist import completed successfully', [
                'provider_id' => $this->providerId,
                'provider_title' => $providerTitle,
                'provider_db_id' => $this->providerDbId,
                'duration_seconds' => $duration,
                'completed_at' => $endTime->toDateTimeString(),
            ]);

        } catch (\Exception $e) {
            $endTime = now();
            $duration = $startTime->diffInSeconds($endTime);

            Log::error('❌ Single dentist import failed', [
                'provider_id' => $this->providerId,
                'provider_db_id' => $this->providerDbId,
                'duration_seconds' => $duration,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'failed_at' => $endTime->toDateTimeString(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Single dentist import job failed', [
            'provider_id' => $this->providerId,
            'provider_db_id' => $this->providerDbId,
            'error' => $exception->getMessage(),
        ]);
    }
}
