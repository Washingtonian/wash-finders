<?php

namespace App\Filament\Resources\PrivateSchoolProviderResource\Pages;

use App\Filament\Resources\PrivateSchoolProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPrivateSchoolProvider extends EditRecord
{
    protected static string $resource = PrivateSchoolProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
