<?php

namespace App\Filament\Resources\HomeResourceProviderResource\Pages;

use App\Filament\Resources\HomeResourceProviderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateHomeResourceProvider extends CreateRecord
{
    protected static string $resource = HomeResourceProviderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'home_resources';

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug('home-resource-'.now()->timestamp);
        }

        return $data;
    }
}
