<?php

namespace App\Filament\Resources\DentistProviderResource\Pages;

use App\Filament\Resources\DentistProviderResource;
use App\Jobs\ProcessSingleProviderJob;
use App\Models\Import;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditDentistProvider extends EditRecord
{
    protected static string $resource = DentistProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh_data')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function () {
                    $this->refreshDentistData();
                })
                ->requiresConfirmation()
                ->modalHeading('Refresh Dentist Data')
                ->modalDescription('This will re-import data for this dentist from the CSV source. This may update photos, addresses, and other information.')
                ->modalSubmitActionLabel('Refresh Data'),
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

    private function refreshDentistData(): void
    {
        $provider = $this->record;
        $providerId = $provider->provider_id;

        Log::info('🔄 Dispatching single dentist refresh job', [
            'provider_id' => $providerId,
            'provider_title' => $provider->meta['title'] ?? 'Unknown',
            'provider_db_id' => $provider->id,
        ]);

        try {
            // Check if dentists import configuration exists
            $import = Import::where('provider_type', 'dentists')->first();

            if (! $import) {
                \Filament\Notifications\Notification::make()
                    ->title('No dentists import found')
                    ->body('Please configure a dentists import first.')
                    ->danger()
                    ->send();

                return;
            }

            // Dispatch the job to the queue
            ProcessSingleProviderJob::dispatch($providerId, $provider->id, 'dentists');

            \Filament\Notifications\Notification::make()
                ->title('Refresh Job Queued')
                ->body('The dentist data refresh has been queued and will start processing shortly.')
                ->success()
                ->send();

            Log::info('✅ Single dentist refresh job dispatched', [
                'provider_id' => $providerId,
                'provider_db_id' => $provider->id,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Failed to dispatch single dentist refresh job', [
                'provider_id' => $providerId,
                'provider_db_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);

            \Filament\Notifications\Notification::make()
                ->title('Job Dispatch Failed')
                ->body('Failed to queue the refresh job: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }
}
