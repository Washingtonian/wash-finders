<?php

namespace App\Filament\Resources\CarWashProviderResource\Pages;

use App\Filament\Resources\CarWashProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCarWashProvider extends EditRecord
{
    protected static string $resource = CarWashProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
