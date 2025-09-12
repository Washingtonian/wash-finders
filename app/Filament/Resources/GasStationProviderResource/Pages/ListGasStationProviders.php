<?php

namespace App\Filament\Resources\GasStationProviderResource\Pages;

use App\Filament\Resources\GasStationProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGasStationProviders extends ListRecords
{
    protected static string $resource = GasStationProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
