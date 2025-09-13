<?php

namespace App\Filament\Resources\RealtorProviderResource\Pages;

use App\Filament\Resources\RealtorProviderResource;
use App\Jobs\ProcessImportJob;
use App\Models\Import;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListRealtorProviders extends ListRecords
{
    protected static string $resource = RealtorProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            Action::make('run_realtors_import')
                ->label('Run Realtors Import')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->action(function () {
                    $import = Import::where('provider_type', 'realtors')->first();

                    if (! $import) {
                        Notification::make()
                            ->title('No realtors import found')
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
                        ->title('Realtors Import Started')
                        ->body('The realtors import has been queued and will start processing shortly.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Run Realtors Import')
                ->modalDescription('This will start the realtors import process.')
                ->modalSubmitActionLabel('Start Import')
                ->visible(fn () => Import::where('provider_type', 'realtors')->exists()),
        ];
    }
}
