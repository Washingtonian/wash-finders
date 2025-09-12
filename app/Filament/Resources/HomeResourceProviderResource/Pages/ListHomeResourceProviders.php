<?php

namespace App\Filament\Resources\HomeResourceProviderResource\Pages;

use App\Filament\Resources\HomeResourceProviderResource;
use App\Models\Import;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHomeResourceProviders extends ListRecords
{
    protected static string $resource = HomeResourceProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('run_home_resources_import')
                ->label('Run Home Resources Import')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->action(function () {
                    $import = Import::where('provider_type', 'home_resources')->first();

                    if (! $import) {
                        \Filament\Notifications\Notification::make()
                            ->title('No home resources import found')
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
                        ->title('Home Resources Import Started')
                        ->body('The home resources import has been queued and will start processing shortly.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Run Home Resources Import')
                ->modalDescription('This will start the home resources import process.')
                ->modalSubmitActionLabel('Start Import')
                ->visible(fn () => Import::where('provider_type', 'home_resources')->exists()),
        ];
    }
}
