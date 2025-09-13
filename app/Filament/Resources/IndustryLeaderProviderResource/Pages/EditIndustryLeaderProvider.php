<?php

namespace App\Filament\Resources\IndustryLeaderProviderResource\Pages;

use App\Filament\Resources\IndustryLeaderProviderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIndustryLeaderProvider extends EditRecord
{
    protected static string $resource = IndustryLeaderProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
