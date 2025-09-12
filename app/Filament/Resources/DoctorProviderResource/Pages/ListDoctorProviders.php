<?php

namespace App\Filament\Resources\DoctorProviderResource\Pages;

use App\Filament\Resources\DoctorProviderResource;
use App\Models\Import;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDoctorProviders extends ListRecords
{
    protected static string $resource = DoctorProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('run_doctors_import')
                ->label('Run Doctors Import')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->action(function () {
                    $import = Import::where('provider_type', 'doctors')->first();

                    if (! $import) {
                        \Filament\Notifications\Notification::make()
                            ->title('No doctors import found')
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
                        ->title('Doctors Import Started')
                        ->body('The doctors import has been queued and will start processing shortly.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Run Doctors Import')
                ->modalDescription('This will start the doctors import process.')
                ->modalSubmitActionLabel('Start Import')
                ->visible(fn () => Import::where('provider_type', 'doctors')->exists()),
        ];
    }
}
