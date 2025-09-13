<?php

namespace App\Filament\Resources\PrivateSchoolProviderResource\Pages;

use App\Filament\Resources\PrivateSchoolProviderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPrivateSchoolProvider extends EditRecord
{
    protected static string $resource = PrivateSchoolProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
