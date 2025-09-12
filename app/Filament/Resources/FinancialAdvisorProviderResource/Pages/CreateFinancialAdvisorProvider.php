<?php

namespace App\Filament\Resources\FinancialAdvisorProviderResource\Pages;

use App\Filament\Resources\FinancialAdvisorProviderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateFinancialAdvisorProvider extends CreateRecord
{
    protected static string $resource = FinancialAdvisorProviderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'financal_advisors';

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug('financal-advisors-'.now()->timestamp);
        }

        return $data;
    }
}
