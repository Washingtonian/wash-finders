<?php

namespace App\Filament\Resources\RealtorProviderResource\Pages;

use App\Filament\Resources\RealtorProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRealtorProvider extends EditRecord
{
    protected static string $resource = RealtorProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
