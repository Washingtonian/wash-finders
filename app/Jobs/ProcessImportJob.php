<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\ImportHistory;
use App\Pipelines\DownloadCsv;
use App\Pipelines\ProcessCsv;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Log;

class ProcessImportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Import $import
    ) {
        $this->onQueue('imports');
    }

    public function handle(): void
    {
        $startTime = now();
        $history = ImportHistory::create([
            'import_id' => $this->import->id,
            'started_at' => $startTime,
            'status' => 'running',
        ]);

        $this->import->update([
            'last_run_status' => 'running',
            'last_run_at' => $startTime,
        ]);

        Log::info('🚀 Import job started', [
            'import_id' => $this->import->id,
            'name' => $this->import->name,
            'provider_type' => $this->import->provider_type,
            'csv_url' => $this->import->csv_url,
            'started_at' => $startTime->toDateTimeString(),
            'job_id' => $this->job->getJobId(),
        ]);

        try {
            // Create a pipeline payload object
            $payload = (object) [
                'type' => $this->import->provider_type,
                'csv_url' => $this->import->csv_url,
                'import_settings' => $this->import->import_settings ?? [],
                'mapping_config' => $this->import->mapping_config ?? [],
                'import_id' => $this->import->id,
                'history_id' => $history->id,
            ];

            Log::info('📋 Pipeline payload created', [
                'import_id' => $this->import->id,
                'payload' => [
                    'type' => $payload->type,
                    'csv_url' => $payload->csv_url,
                    'settings_count' => count($payload->import_settings),
                    'mapping_count' => count($payload->mapping_config),
                ],
            ]);

            // Run the existing pipeline
            $result = app(Pipeline::class)
                ->send($payload)
                ->through([
                    DownloadCsv::class,
                    ProcessCsv::class,
                ])
                ->thenReturn();

            // Update statistics from the result
            $stats = $result->stats ?? [];
            $endTime = now();
            $duration = $startTime->diffInSeconds($endTime);

            $history->update([
                'completed_at' => $endTime,
                'status' => 'completed',
                'records_processed' => $stats['processed'] ?? 0,
                'records_created' => $stats['created'] ?? 0,
                'records_updated' => $stats['updated'] ?? 0,
                'records_skipped' => $stats['skipped'] ?? 0,
                'records_missing' => $stats['missing'] ?? 0,
            ]);

            $this->import->update([
                'last_run_status' => 'completed',
                'records_processed' => $stats['processed'] ?? 0,
                'records_created' => $stats['created'] ?? 0,
                'records_updated' => $stats['updated'] ?? 0,
                'records_skipped' => $stats['skipped'] ?? 0,
                'records_missing' => $stats['missing'] ?? 0,
            ]);

            Log::info('✅ Import completed successfully', [
                'import_id' => $this->import->id,
                'name' => $this->import->name,
                'duration_seconds' => $duration,
                'stats' => $stats,
                'completed_at' => $endTime->toDateTimeString(),
            ]);

        } catch (\Exception $e) {
            $endTime = now();
            $duration = $startTime->diffInSeconds($endTime);

            Log::error('❌ Import failed', [
                'import_id' => $this->import->id,
                'name' => $this->import->name,
                'duration_seconds' => $duration,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'failed_at' => $endTime->toDateTimeString(),
            ]);

            $history->update([
                'completed_at' => $endTime,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $this->import->update([
                'last_run_status' => 'failed',
                'last_run_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Import job failed', [
            'import_id' => $this->import->id,
            'error' => $exception->getMessage(),
        ]);

        $this->import->update([
            'last_run_status' => 'failed',
            'last_run_error' => $exception->getMessage(),
        ]);
    }
}
