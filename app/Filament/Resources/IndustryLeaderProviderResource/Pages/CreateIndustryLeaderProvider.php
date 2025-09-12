<?php

namespace App\Filament\Resources\IndustryLeaderProviderResource\Pages;

use App\Filament\Resources\IndustryLeaderProviderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateIndustryLeaderProvider extends CreateRecord
{
    protected static string $resource = IndustryLeaderProviderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'industry_leaders';

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug('industry-leaders-'.now()->timestamp);
        }

        return $data;
    }
}
