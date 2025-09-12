<?php

namespace App\Filament\Resources\DoctorProviderResource\Pages;

use App\Filament\Resources\DoctorProviderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateDoctorProvider extends CreateRecord
{
    protected static string $resource = DoctorProviderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'doctors';

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug('doctor-'.now()->timestamp);
        }

        return $data;
    }
}
