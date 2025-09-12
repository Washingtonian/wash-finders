<?php

namespace App\Filament\Resources\PrivateSchoolProviderResource\Pages;

use App\Filament\Resources\PrivateSchoolProviderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreatePrivateSchoolProvider extends CreateRecord
{
    protected static string $resource = PrivateSchoolProviderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'private_schools';

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug('private-schools-'.now()->timestamp);
        }

        return $data;
    }
}
