<?php

namespace App\Filament\Resources\RealtorProviderResource\Pages;

use App\Filament\Resources\RealtorProviderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateRealtorProvider extends CreateRecord
{
    protected static string $resource = RealtorProviderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'realtors';

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug('realtors-'.now()->timestamp);
        }

        return $data;
    }
}
