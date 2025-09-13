<?php

namespace App\Filament\Resources\PetVendorProviderResource\Pages;

use App\Filament\Resources\PetVendorProviderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPetVendorProvider extends EditRecord
{
    protected static string $resource = PetVendorProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
