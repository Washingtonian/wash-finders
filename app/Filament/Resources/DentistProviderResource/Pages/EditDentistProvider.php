<?php

namespace App\Filament\Resources\DentistProviderResource\Pages;

use App\Filament\Resources\DentistProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDentistProvider extends EditRecord
{
    protected static string $resource = DentistProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['meta']['enhanced_photo_path'])) {
            $path = $data['meta']['enhanced_photo_path'];
            // Remove leading slash and convert to relative path for Storage
            $path = ltrim($path, '/');
            if (str_starts_with($path, 'storage/')) {
                $path = substr($path, 8); // Remove 'storage/' prefix
            }
            $data['photo'] = $path;
        }

        // Handle enhanced profile text and bio fields
        if (isset($data['meta']['enhanced-profile-text'])) {
            $data['enhanced_profile_text'] = $data['meta']['enhanced-profile-text'];
        }
        if (isset($data['meta']['enhanced_profile_text_path'])) {
            $data['enhanced_profile_text_path'] = $data['meta']['enhanced_profile_text_path'];
        }
        if (isset($data['meta']['bio'])) {
            $data['bio'] = $data['meta']['bio'];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure URLs are generated with HTTPS
        if (isset($data['photo']) && $data['photo']) {
            $data['meta']['enhanced_photo_path'] = '/storage/'.$data['photo'];
        }

        // Save enhanced profile text and bio to meta
        if (isset($data['enhanced_profile_text'])) {
            $data['meta']['enhanced-profile-text'] = $data['enhanced_profile_text'];
        }
        if (isset($data['bio'])) {
            $data['meta']['bio'] = $data['bio'];
        }

        return $data;
    }
}
