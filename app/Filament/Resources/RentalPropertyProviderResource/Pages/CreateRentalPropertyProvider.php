<?php

namespace App\Filament\Resources\RentalPropertyProviderResource\Pages;

use App\Filament\Resources\RentalPropertyProviderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateRentalPropertyProvider extends CreateRecord
{
    protected static string $resource = RentalPropertyProviderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'rental_properties';

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug('rental-properties-'.now()->timestamp);
        }

        return $data;
    }
}
