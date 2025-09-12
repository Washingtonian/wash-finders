<?php

namespace App\Pipelines;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class FormatRecord
{
    public function __invoke($record, \Closure $next)
    {

        $formattedRecord = collect($record)
            ->filter(fn ($value) => ! is_null($value) && $value !== '')
            ->mapWithKeys(function ($value, $key) use ($record) {
                $key = Str::slug($key);
                $value = $this->formatValue($value, $key);

                if (Str::endsWith($key, '-timestamp') || Str::endsWith($key, 'end-date')) {
                    $value = $this->convertToTimestamp($value);
                }

                if (in_array($key, ['neighborhoods', 'licensed-states'])) {
                    $value = $this->formatAsArray($value);
                }

                if ($key === 'url') {
                    $value = $this->formatUrl($value);
                }
                if (Str::startsWith($key, 'email')) {
                    $formattedEmail = $this->formatEmail($value);
                    $emailKey = 'emails';
                    if (! isset($formattedRecord[$emailKey])) {
                        $formattedRecord[$emailKey] = [];
                    }
                    $formattedRecord[$emailKey][] = $formattedEmail;

                    return [$emailKey => $formattedRecord[$emailKey]];
                }

                if (Str::contains($key, 'specialty')) {
                    $specialties = $this->extractFieldsStartingWith($record, 'specialty');
                    if (! empty($specialties)) {
                        return ['specialties' => $specialties];
                    }
                }

                if (Str::contains($key, 'interests')) {
                    $interests = $this->extractFieldsStartingWith($record, 'interests');
                    if (! empty($interests)) {
                        return ['interests' => $interests];
                    }
                }

                // if (Str::contains($key, 'special-interests')) {
                //     $interests = $this->extractFieldsStartingWith($record, 'special-interests');
                //     if (!empty($interests)) {
                //         return ['interests' => $interests];
                //     }
                // }
                return [$key === 'post-title-automatic' ? 'title' : $key => $value];
            })
            ->toArray();

        return $next($formattedRecord);
    }

    private function formatValue($value, $key)
    {
        return app(Pipeline::class)
            ->send($value)
            ->through([
                fn ($value, $next) => $value === '1' || $value === '0' ? (bool) $value : $next($value),
                fn ($value, $next) => $key === 'enhanced-profile-text' ? trim(preg_replace('/(<style.*?<\/style>)|<[^>]*>|\s+/', ' ', $value)) : $next($value),
                fn ($value, $next) => is_string($value) ? trim(preg_replace('/\s+/', ' ', $value)) : $next($value),
            ])
            ->thenReturn();
    }

    private function convertToTimestamp($value)
    {
        return Carbon::parse($value)->timestamp;
    }

    private function formatUrl($url): string
    {
        return Str::lower(! preg_match('~^(?:f|ht)tps?://~i', $url) ? 'https://'.$url : $url);
    }

    public function formatAsArray($value)
    {
        if (is_string($value)) {
            return array_map('trim', explode(',', $value));
        }

        return array_unique(array_map('trim', $value));
    }

    private function formatEmail($email)
    {
        return Str::lower(filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null);
    }

    private function extractFieldsStartingWith($record, $prefix)
    {
        return collect($record)
            ->filter(fn ($v, $k) => Str::startsWith(Str::slug($k), $prefix))
            ->filter()
            ->values()
            ->toArray();
    }
}
