<?php

namespace App\Filament\Resources\RentalPropertyProviderResource\Pages;

use App\Filament\Resources\RentalPropertyProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRentalPropertyProvider extends EditRecord
{
    protected static string $resource = RentalPropertyProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
