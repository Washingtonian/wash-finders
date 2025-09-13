<?php

namespace App\Filament\Resources\DoctorProviderResource\Pages;

use App\Filament\Resources\DoctorProviderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDoctorProvider extends EditRecord
{
    protected static string $resource = DoctorProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['meta']['latitude'])) {
            $data['latitude'] = $data['meta']['latitude'];
        }
        if (isset($data['meta']['longitude'])) {
            $data['longitude'] = $data['meta']['longitude'];
        }

        return $data;
    }
}
