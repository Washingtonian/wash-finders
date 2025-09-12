<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportHistory extends Model
{
    protected $fillable = [
        'import_id',
        'started_at',
        'completed_at',
        'status',
        'error_message',
        'records_processed',
        'records_created',
        'records_updated',
        'records_skipped',
        'records_missing',
        'processing_log',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'processing_log' => 'array',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    public function getDurationAttribute(): ?string
    {
        if (! $this->completed_at) {
            return null;
        }

        $duration = $this->started_at->diffInSeconds($this->completed_at);

        if ($duration < 60) {
            return "{$duration}s";
        }

        if ($duration < 3600) {
            return round($duration / 60).'m';
        }

        return round($duration / 3600).'h';
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'success',
            'failed' => 'danger',
            'running' => 'warning',
            'pending' => 'gray',
            default => 'gray',
        };
    }
}
