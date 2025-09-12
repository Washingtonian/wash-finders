<?php

namespace App\Filament\Resources\DentistProviderResource\Pages;

use App\Filament\Resources\DentistProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDentistProvider extends EditRecord
{
    protected static string $resource = DentistProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
