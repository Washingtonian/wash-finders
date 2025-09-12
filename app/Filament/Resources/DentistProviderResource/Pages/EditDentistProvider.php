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
        // Handle photo
        if (isset($data['meta']['enhanced_photo_path'])) {
            $path = $data['meta']['enhanced_photo_path'];
            // Remove leading slash and convert to relative path for Storage
            $path = ltrim($path, '/');
            if (str_starts_with($path, 'storage/')) {
                $path = substr($path, 8); // Remove 'storage/' prefix
            }
            $data['photo'] = $path;
        }

        // Handle all form fields from meta with proper field mapping
        $fieldMappings = [
            'title' => 'title',
            'business_name' => 'business-name',
            'website' => 'website',
            'email' => 'email',
            'phone' => 'phone',
            'facebook_url' => 'facebook_url',
            'twitter_url' => 'twitter_url',
            'instagram_url' => 'instagram_url',
            'linkedin_url' => 'linkedin_url',
            'specialty' => 'specialty',
            'specialties' => 'specialties',
            'best_of_washingtonian' => 'best_of_washingtonian',
            'address_street' => 'address-street',
            'address_city' => 'address-city',
            'address_state' => 'address-state',
            'address_zip' => 'address-zip',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'enhanced_profile_text' => 'enhanced-profile-text',
            'enhanced_profile_text_path' => 'enhanced-profile-text-filename',
            'advanced_text' => 'advanced_text',
            'additional_data' => 'additional_data',
        ];

        foreach ($fieldMappings as $formField => $metaField) {
            if (isset($data['meta'][$metaField])) {
                $data[$formField] = $data['meta'][$metaField];
            }
        }

        // Handle legacy field names
        if (isset($data['meta']['enhanced-profile-text'])) {
            $data['enhanced_profile_text'] = $data['meta']['enhanced-profile-text'];
        }
        if (isset($data['meta']['enhanced_profile_text_path'])) {
            $data['enhanced_profile_text_path'] = $data['meta']['enhanced_profile_text_path'];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle photo
        if (isset($data['photo']) && $data['photo']) {
            $data['meta']['enhanced_photo_path'] = '/storage/'.$data['photo'];
        }

        // Save all form fields to meta with proper field mapping
        $fieldMappings = [
            'title' => 'title',
            'business_name' => 'business-name',
            'website' => 'website',
            'email' => 'email',
            'phone' => 'phone',
            'facebook_url' => 'facebook_url',
            'twitter_url' => 'twitter_url',
            'instagram_url' => 'instagram_url',
            'linkedin_url' => 'linkedin_url',
            'specialty' => 'specialty',
            'specialties' => 'specialties',
            'best_of_washingtonian' => 'best_of_washingtonian',
            'address_street' => 'address-street',
            'address_city' => 'address-city',
            'address_state' => 'address-state',
            'address_zip' => 'address-zip',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'enhanced_profile_text' => 'enhanced-profile-text',
            'enhanced_profile_text_path' => 'enhanced-profile-text-filename',
            'advanced_text' => 'advanced_text',
            'additional_data' => 'additional_data',
        ];

        foreach ($fieldMappings as $formField => $metaField) {
            if (isset($data[$formField])) {
                $data['meta'][$metaField] = $data[$formField];
                unset($data[$formField]); // Remove from main data array
            }
        }

        // Handle legacy field names
        if (isset($data['enhanced_profile_text'])) {
            $data['meta']['enhanced-profile-text'] = $data['enhanced_profile_text'];
        }

        return $data;
    }
}
