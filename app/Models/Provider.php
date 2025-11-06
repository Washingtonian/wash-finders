<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\SchemalessAttributes\Casts\SchemalessAttributes;

class Provider extends Model
{
    use SoftDeletes;
    use Searchable;

    protected $fillable = [
        'id',
        'type',
        'provider_id',
        'meta',
        'slug',
    ];

    protected $casts = [
        'meta' => SchemalessAttributes::class,
    ];

    public function toSearchableArray(): array
    {
        $array = $this->toArray();

        return $array;
    }

    public function searchableAs(): string
    {
        return 'providers_index';
    }

    public function scopeWithExtraAttributes(): Builder
    {
        return $this->meta->modelScope();
    }

    public static function getAvailableTypes(): array
    {
        // Get types from imports table as source of truth
        return Import::distinct()
            ->whereNotNull('provider_type')
            ->pluck('provider_type')
            ->filter()
            ->mapWithKeys(fn ($type) => [
                $type => static::getTypeLabel($type),
            ])
            ->toArray();
    }

    public static function getTypeLabel(string $type): string
    {
        // Get label from imports table if available, otherwise format the type name
        $import = Import::where('provider_type', $type)->first();
        if ($import) {
            return $import->name ?? ucwords(str_replace('_', ' ', $type));
        }

        return ucwords(str_replace('_', ' ', $type));
    }

    public static function getTypeIcon(string $type): string
    {
        return match ($type) {
            'dentists' => 'heroicon-o-user-circle',
            'doctors' => 'heroicon-o-heart',
            'financal_advisors' => 'heroicon-o-banknotes',
            'home_resources' => 'heroicon-o-wrench-screwdriver',
            'industry_leaders' => 'heroicon-o-star',
            'lawyers' => 'heroicon-o-scale',
            'mortgage_professionals' => 'heroicon-o-home',
            'pet_vendors' => 'heroicon-o-heart',
            'private_schools' => 'heroicon-o-academic-cap',
            'realtors' => 'heroicon-o-building-office-2',
            'rental_properties' => 'heroicon-o-home',
            'retirement_communities' => 'heroicon-o-building-office',
            'wedding_vendors' => 'heroicon-o-heart',
            default => 'heroicon-o-building-office-2'
        };
    }
}
