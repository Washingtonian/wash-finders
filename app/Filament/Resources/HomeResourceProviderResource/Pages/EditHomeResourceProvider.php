<?php

namespace App\Filament\Resources\HomeResourceProviderResource\Pages;

use App\Filament\Resources\HomeResourceProviderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHomeResourceProvider extends EditRecord
{
    protected static string $resource = HomeResourceProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
