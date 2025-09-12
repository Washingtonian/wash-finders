<?php

namespace App\Filament\Resources\MortgageProfessionalProviderResource\Pages;

use App\Filament\Resources\MortgageProfessionalProviderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateMortgageProfessionalProvider extends CreateRecord
{
    protected static string $resource = MortgageProfessionalProviderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'mortgage_professionals';

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug('mortgage-professionals-'.now()->timestamp);
        }

        return $data;
    }
}
