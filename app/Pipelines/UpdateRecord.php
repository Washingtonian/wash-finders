<?php

namespace App\Pipelines;

use App\Models\Provider;
use Illuminate\Support\Str;

class UpdateRecord
{
    public function __invoke($record, \Closure $next)
    {
        // Ensure we have the required fields
        $providerId = $record['id'] ?? $record['provider_id'] ?? null;
        $title = $record['title'] ?? $record['post-title-automatic'] ?? 'Untitled';
        $type = $record['type'] ?? 'unknown';

        // Skip records with empty or missing provider ID
        if (! $providerId || trim($providerId) === '') {
            $record['skipped'] = true;
            $record['created'] = false;
            $record['updated'] = false;

            return $next($record);
        }

        $existingProvider = Provider::where('provider_id', $providerId)
            ->where('type', $type)
            ->first();

        $provider = Provider::updateOrCreate(
            [
                'provider_id' => $providerId,
                'type' => $type,
                'slug' => Str::slug($title),
            ],
            [
                'meta' => $record,
            ]
        );

        // Add tracking information to the record
        $record['created'] = ! $existingProvider;
        $record['updated'] = (bool) $existingProvider;

        return $next($record);
    }
}
