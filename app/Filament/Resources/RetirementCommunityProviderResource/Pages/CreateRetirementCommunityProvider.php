<?php

namespace App\Filament\Resources\RetirementCommunityProviderResource\Pages;

use App\Filament\Resources\RetirementCommunityProviderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateRetirementCommunityProvider extends CreateRecord
{
    protected static string $resource = RetirementCommunityProviderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'retirement_communities';

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug('retirement-communities-'.now()->timestamp);
        }

        return $data;
    }
}
