<?php

namespace App\Filament\Resources\DentistProviderResource\Pages;

use App\Filament\Resources\DentistProviderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateDentistProvider extends CreateRecord
{
    protected static string $resource = DentistProviderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'dentists';

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug('dentist-'.now()->timestamp);
        }

        return $data;
    }
}
