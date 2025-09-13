<?php

namespace App\Filament\Resources\MortgageProfessionalProviderResource\Pages;

use App\Filament\Resources\MortgageProfessionalProviderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMortgageProfessionalProvider extends EditRecord
{
    protected static string $resource = MortgageProfessionalProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
