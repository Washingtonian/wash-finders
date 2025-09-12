<?php

namespace App\Filament\Resources\DentistProviderResource\Pages;

use App\Filament\Resources\DentistProviderResource;
use App\Filament\Resources\DentistProviderResource\Widgets\DentistStatsWidget;
use App\Models\Import;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDentistProviders extends ListRecords
{
    protected static string $resource = DentistProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('run_dentists_import')
                ->label('Run Dentists Import')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->action(function () {
                    $import = Import::where('provider_type', 'dentists')->first();

                    if (! $import) {
                        \Filament\Notifications\Notification::make()
                            ->title('No dentists import found')
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
                        ->title('Dentists Import Started')
                        ->body('The dentists import has been queued and will start processing shortly.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Run Dentists Import')
                ->modalDescription('This will start the dentists import process.')
                ->modalSubmitActionLabel('Start Import')
                ->visible(fn () => Import::where('provider_type', 'dentists')->exists()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DentistStatsWidget::class,
        ];
    }
}
