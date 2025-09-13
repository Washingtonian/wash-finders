<?php

namespace App\Filament\Resources\RentalPropertyProviderResource\Pages;

use App\Filament\Resources\RentalPropertyProviderResource;
use App\Jobs\ProcessImportJob;
use App\Models\Import;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListRentalPropertyProviders extends ListRecords
{
    protected static string $resource = RentalPropertyProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            Action::make('run_rental_properties_import')
                ->label('Run Rental Properties Import')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->action(function () {
                    $import = Import::where('provider_type', 'rental_properties')->first();

                    if (! $import) {
                        Notification::make()
                            ->title('No rental properties import found')
                            ->danger()
                            ->send();

                        return;
                    }

                    ProcessImportJob::dispatch($import);

                    $import->update([
                        'last_run_status' => 'running',
                        'last_run_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Rental Properties Import Started')
                        ->body('The rental properties import has been queued and will start processing shortly.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Run Rental Properties Import')
                ->modalDescription('This will start the rental properties import process.')
                ->modalSubmitActionLabel('Start Import')
                ->visible(fn () => Import::where('provider_type', 'rental_properties')->exists()),
        ];
    }
}
