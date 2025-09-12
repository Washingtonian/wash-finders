<?php

namespace App\Filament\Resources\LawyerProviderResource\Pages;

use App\Filament\Resources\LawyerProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLawyerProvider extends EditRecord
{
    protected static string $resource = LawyerProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
