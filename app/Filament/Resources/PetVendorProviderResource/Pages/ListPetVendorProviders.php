<?php

namespace App\Filament\Resources\PetVendorProviderResource\Pages;

use App\Filament\Resources\PetVendorProviderResource;
use App\Models\Import;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPetVendorProviders extends ListRecords
{
    protected static string $resource = PetVendorProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('run_pet_vendors_import')
                ->label('Run Pet Vendors Import')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->action(function () {
                    $import = Import::where('provider_type', 'pet_vendors')->first();

                    if (! $import) {
                        \Filament\Notifications\Notification::make()
                            ->title('No pet vendors import found')
                            ->danger()
                            ->send();

                        return;
                    }

                    \App\Jobs\ProcessImportJob::dispatch($import);

                    $import->update([
                        'last_run_status' => 'running',
                        'last_run_at' => now(),
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Pet Vendors Import Started')
                        ->body('The pet vendors import has been queued and will start processing shortly.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Run Pet Vendors Import')
                ->modalDescription('This will start the pet vendors import process.')
                ->modalSubmitActionLabel('Start Import')
                ->visible(fn () => Import::where('provider_type', 'pet_vendors')->exists()),
        ];
    }
}
