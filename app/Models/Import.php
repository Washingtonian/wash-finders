<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Import extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'provider_type',
        'description',
        'csv_url',
        'version',
        'is_active',
        'is_current_version',
        'import_settings',
        'mapping_config',
        'last_run_at',
        'last_run_status',
        'last_run_error',
        'records_processed',
        'records_created',
        'records_updated',
        'records_skipped',
        'records_missing',
        'schedule_enabled',
        'schedule_frequency',
        'schedule_time',
        'schedule_days',
    ];

    protected $casts = [
        'import_settings' => 'array',
        'mapping_config' => 'array',
        'schedule_days' => 'array',
        'is_active' => 'boolean',
        'is_current_version' => 'boolean',
        'schedule_enabled' => 'boolean',
        'last_run_at' => 'datetime',
        'schedule_time' => 'datetime',
    ];

    public function providerType()
    {
        return $this->belongsTo(Provider::class, 'provider_type', 'type');
    }

    public function importHistories(): HasMany
    {
        return $this->hasMany(ImportHistory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrentVersion($query)
    {
        return $query->where('is_current_version', true);
    }

    public function scopeForProviderType($query, string $type)
    {
        return $query->where('provider_type', $type);
    }

    public function scopeScheduled($query)
    {
        return $query->where('schedule_enabled', true);
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->last_run_status) {
            'completed' => 'success',
            'failed' => 'danger',
            'running' => 'warning',
            'pending' => 'gray',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->last_run_status) {
            'completed' => 'Completed',
            'failed' => 'Failed',
            'running' => 'Running',
            'pending' => 'Pending',
            default => 'Never Run',
        };
    }

    public function getLastRunSummaryAttribute(): string
    {
        if (! $this->last_run_at || $this->last_run_at === null) {
            return 'Never run';
        }

        try {
            $summary = "Last run: {$this->last_run_at->format('m/d/Y g:i a')}";

            if ($this->records_created > 0) {
                $summary .= ", {$this->records_created} Providers created";
            }

            if ($this->records_updated > 0) {
                $summary .= ", {$this->records_updated} updated";
            }

            if ($this->records_skipped > 0) {
                $summary .= ", {$this->records_skipped} skipped";
            }

            if ($this->records_missing > 0) {
                $summary .= ", {$this->records_missing} missing";
            }

            return $summary;
        } catch (\Exception $e) {
            return 'Error generating summary';
        }
    }

    public function canRunImport(): bool
    {
        return $this->is_active &&
               $this->csv_url &&
               $this->last_run_status !== 'running';
    }

    public function markAsCurrentVersion(): void
    {
        // Remove current version flag from other imports of same type
        static::forProviderType($this->provider_type)
            ->where('id', '!=', $this->id)
            ->update(['is_current_version' => false]);

        // Set this as current version
        $this->update(['is_current_version' => true]);
    }
}
