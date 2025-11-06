<?php

namespace App\Filament\Resources\DoctorProviderResource\Pages;

use App\Filament\Resources\DoctorProviderResource;
use App\Filament\Resources\ImportResource;
use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Models\Provider;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListDoctorProviders extends ListRecords
{
    protected static string $resource = DoctorProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            Action::make('finder_settings')
                ->label('Finder Settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->url(function () {
                    $import = Import::where('provider_type', 'doctors')->first();
                    
                    if ($import) {
                        return ImportResource::getUrl('edit', ['record' => $import]);
                    }
                    
                    // If no import exists, create a new one
                    return ImportResource::getUrl('create', ['provider_type' => 'doctors']);
                })
                ->openUrlInNewTab(false),

            Action::make('index_to_algolia')
                ->label('Index to Algolia')
                ->icon('heroicon-o-magnifying-glass')
                ->color('success')
                ->action(function () {
                    $count = Provider::where('type', 'doctors')
                        ->whereNull('deleted_at')
                        ->count();

                    if ($count === 0) {
                        Notification::make()
                            ->title('No doctors to index')
                            ->body('There are no doctors available to index.')
                            ->warning()
                            ->send();

                        return;
                    }

                    // Index all doctors to Algolia in chunks
                    Provider::where('type', 'doctors')
                        ->whereNull('deleted_at')
                        ->chunk(100, function ($providers) {
                            $providers->searchable();
                        });

                    Notification::make()
                        ->title('Indexing Started')
                        ->body("Indexing {$count} doctors to Algolia. This may take a few moments.")
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Index Doctors to Algolia')
                ->modalDescription('This will index all doctors to Algolia for search. This may take a few moments depending on the number of records.')
                ->modalSubmitActionLabel('Start Indexing'),

            Action::make('run_doctors_import')
                ->label('Run Doctors Import')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->action(function () {
                    $import = Import::where('provider_type', 'doctors')->first();

                    if (! $import) {
                        Notification::make()
                            ->title('No doctors import found')
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
