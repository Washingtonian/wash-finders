<?php

namespace App\Filament\Resources\DoctorProviderResource\Pages;

use App\Filament\Resources\DoctorProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDoctorProvider extends EditRecord
{
    protected static string $resource = DoctorProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
