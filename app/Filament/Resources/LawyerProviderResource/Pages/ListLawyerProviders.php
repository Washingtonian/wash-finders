<?php

namespace App\Filament\Resources\LawyerProviderResource\Pages;

use App\Filament\Resources\LawyerProviderResource;
use App\Models\Import;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLawyerProviders extends ListRecords
{
    protected static string $resource = LawyerProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('run_lawyers_import')
                ->label('Run Lawyers Import')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->action(function () {
                    $import = Import::where('provider_type', 'lawyers')->first();

                    if (! $import) {
                        \Filament\Notifications\Notification::make()
                            ->title('No lawyers import found')
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
                        ->title('Lawyers Import Started')
                        ->body('The lawyers import has been queued and will start processing shortly.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Run Lawyers Import')
                ->modalDescription('This will start the lawyers import process.')
                ->modalSubmitActionLabel('Start Import')
                ->visible(fn () => Import::where('provider_type', 'lawyers')->exists()),
        ];
    }
}
