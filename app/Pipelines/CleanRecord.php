<?php

namespace App\Pipelines;

class CleanRecord
{
    public function __invoke($record, $next)
    {
        $cleanedRecord = collect($record)
            ->reject(fn ($value, $key) => in_array(substr($key, 0, 2), ['a-', 'b-', 'c-', 'd-']))
            ->filter(fn ($value) => ! is_null($value))
            ->toArray();

        return $next($cleanedRecord);
    }
}
