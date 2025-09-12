<?php

namespace App\Pipelines;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Statement;

class ProcessCsv
{
    private $stats = [
        'processed' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'missing' => 0,
    ];

    public function __invoke($payload, \Closure $next)
    {
        try {
            $localPath = Storage::path($payload->fileName);
            $csv = Reader::createFromPath($localPath, 'r');
            $csv->setHeaderOffset(0);
            $records = Statement::create()->process($csv);

            $importSettings = $payload->import_settings ?? [];
            $mappingConfig = $payload->mapping_config ?? [];
            $maxRecords = $importSettings['max_records'] ?? 1000;
            $providerType = $payload->type;

            Log::info('📊 Starting CSV processing', [
                'import_id' => $payload->import_id ?? 'unknown',
                'filename' => $payload->fileName ?? 'unknown',
                'total_records' => count($records),
                'max_records' => $maxRecords,
                'mapping_config' => $mappingConfig,
                'provider_type' => $providerType,
            ]);

            $processed = 0;
            $totalRecords = count($records);
            $lastProgressLog = 0;

            foreach ($records as $record) {
                if ($processed >= $maxRecords) {
                    Log::info('⏹️ Reached max records limit', [
                        'import_id' => $payload->import_id ?? 'unknown',
                        'max_records' => $maxRecords,
                        'processed' => $processed,
                    ]);
                    break;
                }

                try {
                    $this->processRecord($providerType, $record, $mappingConfig, $importSettings);
                    $this->stats['processed']++;
                    $processed++;

                    // Log progress every 100 records or at the end
                    if ($processed % 100 === 0 || $processed === $totalRecords) {
                        $progressPercent = round(($processed / $totalRecords) * 100, 1);
                        Log::info('📈 Processing progress', [
                            'import_id' => $payload->import_id ?? 'unknown',
                            'processed' => $processed,
                            'total' => $totalRecords,
                            'progress_percent' => $progressPercent,
                            'stats' => $this->stats,
                        ]);
                        $lastProgressLog = $processed;
                    }

                } catch (\Exception $e) {
                    Log::warning('⚠️ Error processing record', [
                        'import_id' => $payload->import_id ?? 'unknown',
                        'error' => $e->getMessage(),
                        'record_index' => $processed,
                        'record_data' => array_slice($record, 0, 5), // Only log first 5 fields
                    ]);
                    $this->stats['skipped']++;
                }
            }

            Log::info('✅ CSV processing completed', [
                'import_id' => $payload->import_id ?? 'unknown',
                'filename' => $payload->fileName ?? 'unknown',
                'stats' => $this->stats,
            ]);

        } catch (\Exception $e) {
            Log::error('CSV processing failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        // Add stats to payload for the job to use
        $payload->stats = $this->stats;

        return $next($payload);
    }

    private function processRecord(string $type, array $record, array $mappingConfig, array $importSettings)
    {
        // Apply mapping configuration if provided
        $mappedRecord = [];
        foreach ($mappingConfig as $csvColumn => $providerField) {
            if (isset($record[$csvColumn]) && ! empty($record[$csvColumn])) {
                $mappedRecord[$providerField] = $record[$csvColumn];
            }
        }

        // If no mapping config, use original record
        if (empty($mappedRecord)) {
            $mappedRecord = $record;
        }

        $processedRecord = app(Pipeline::class)
            ->send($mappedRecord)
            ->through([
                fn ($record, $next) => $next(array_merge($record, ['type' => $type])),
                FormatRecord::class,
                AddLocations::class,
                DownloadPhoto::class,
                CleanRecord::class,
                UpdateRecord::class,
            ])
            ->thenReturn();

        // Track statistics
        if (isset($processedRecord['created']) && $processedRecord['created']) {
            $this->stats['created']++;
        } elseif (isset($processedRecord['updated']) && $processedRecord['updated']) {
            $this->stats['updated']++;
        } else {
            $this->stats['skipped']++;
        }
    }
}
