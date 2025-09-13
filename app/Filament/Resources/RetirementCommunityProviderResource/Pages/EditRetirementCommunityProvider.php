<?php

namespace App\Filament\Resources\RetirementCommunityProviderResource\Pages;

use App\Filament\Resources\RetirementCommunityProviderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRetirementCommunityProvider extends EditRecord
{
    protected static string $resource = RetirementCommunityProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
