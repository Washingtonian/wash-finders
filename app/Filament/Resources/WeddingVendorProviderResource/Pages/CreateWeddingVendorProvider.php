<?php

namespace App\Filament\Resources\WeddingVendorProviderResource\Pages;

use App\Filament\Resources\WeddingVendorProviderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateWeddingVendorProvider extends CreateRecord
{
    protected static string $resource = WeddingVendorProviderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'wedding_vendors';

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug('wedding-vendors-'.now()->timestamp);
        }

        return $data;
    }
}
