<?php

namespace App\Filament\Resources\PetVendorProviderResource\Pages;

use App\Filament\Resources\PetVendorProviderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreatePetVendorProvider extends CreateRecord
{
    protected static string $resource = PetVendorProviderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'pet_vendors';

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug('pet-vendors-'.now()->timestamp);
        }

        return $data;
    }
}
