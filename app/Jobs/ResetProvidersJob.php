<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\Provider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ResetProvidersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  string|null  $providerType  Specific provider type to reset. Null or 'all' targets every known type.
     */
    public function __construct(public ?string $providerType = null)
    {
        $this->onQueue('imports');
    }

    public function handle(): void
    {
        $types = $this->resolveProviderTypes();

        foreach ($types as $type) {
            if (! $type) {
                continue;
            }

            $this->resetProvidersForType($type);
            $this->dispatchImportForType($type);
        }
    }

    protected function resolveProviderTypes(): Collection
    {
        if ($this->providerType && $this->providerType !== 'all') {
            return collect([$this->providerType]);
        }

        $importTypes = Import::query()
            ->select('provider_type')
            ->distinct()
            ->pluck('provider_type')
            ->filter();

        $configTypes = collect(config('providers', []))
            ->keys()
            ->filter();

        return $importTypes
            ->merge($configTypes)
            ->unique()
            ->values();
    }

    protected function resetProvidersForType(string $type): void
    {
        $existingCount = Provider::withTrashed()->where('type', $type)->count();

        Log::info('🔁 Resetting providers before resync', [
            'provider_type' => $type,
            'existing_count' => $existingCount,
        ]);

        Provider::withTrashed()
            ->where('type', $type)
            ->chunkById(500, function ($providers) {
                foreach ($providers as $provider) {
                    if (method_exists($provider, 'unsearchable')) {
                        $provider->unsearchable();
                    }
                    $provider->forceDelete();
                }
            });
    }

    protected function dispatchImportForType(string $type): void
    {
        $import = Import::query()
            ->where('provider_type', $type)
            ->orderByDesc('is_current_version')
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->first();

        if ($import && $import->csv_url) {
            $import->update([
                'last_run_status' => 'pending',
                'last_run_error' => null,
            ]);

            Log::info('📤 Queuing import after reset', [
                'provider_type' => $type,
                'import_id' => $import->id,
                'import_name' => $import->name,
            ]);

            ProcessImportJob::dispatch($import);

            return;
        }

        $csvUrl = config("providers.{$type}");

        if ($csvUrl) {
            Log::info('📤 Queuing legacy CSV import after reset', [
                'provider_type' => $type,
                'csv_url' => $csvUrl,
            ]);

            ProcessCsvJob::dispatch($type, $csvUrl);

            return;
        }

        Log::warning('⚠️ No import configuration found for provider type; skipping resync', [
            'provider_type' => $type,
        ]);
    }
}


