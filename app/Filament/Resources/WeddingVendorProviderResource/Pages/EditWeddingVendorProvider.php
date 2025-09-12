<?php

namespace App\Filament\Resources\WeddingVendorProviderResource\Pages;

use App\Filament\Resources\WeddingVendorProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWeddingVendorProvider extends EditRecord
{
    protected static string $resource = WeddingVendorProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
