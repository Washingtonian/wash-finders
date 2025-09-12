<?php

namespace App\Filament\Resources\GasStationProviderResource\Pages;

use App\Filament\Resources\GasStationProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGasStationProvider extends EditRecord
{
    protected static string $resource = GasStationProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
