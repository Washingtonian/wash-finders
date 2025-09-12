<?php

namespace App\Filament\Resources\FinancialAdvisorProviderResource\Pages;

use App\Filament\Resources\FinancialAdvisorProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFinancialAdvisorProvider extends EditRecord
{
    protected static string $resource = FinancialAdvisorProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
