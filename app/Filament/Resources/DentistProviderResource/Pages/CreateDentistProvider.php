<?php

namespace App\Filament\Resources\DentistProviderResource\Pages;

use App\Filament\Resources\DentistProviderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateDentistProvider extends CreateRecord
{
    protected static string $resource = DentistProviderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'dentists';

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug('dentist-'.now()->timestamp);
        }

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
