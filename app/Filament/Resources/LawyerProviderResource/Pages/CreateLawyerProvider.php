<?php

namespace App\Filament\Resources\LawyerProviderResource\Pages;

use App\Filament\Resources\LawyerProviderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateLawyerProvider extends CreateRecord
{
    protected static string $resource = LawyerProviderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'lawyers';

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug('lawyers-'.now()->timestamp);
        }

        return $data;
    }
}
