<?php

namespace App\Filament\Resources\RetirementCommunityProviderResource\Pages;

use App\Filament\Resources\RetirementCommunityProviderResource;
use App\Jobs\ProcessImportJob;
use App\Models\Import;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListRetirementCommunityProviders extends ListRecords
{
    protected static string $resource = RetirementCommunityProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            Action::make('run_retirement_communities_import')
                ->label('Run Retirement Communities Import')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->action(function () {
                    $import = Import::where('provider_type', 'retirement_communities')->first();

                    if (! $import) {
                        Notification::make()
                            ->title('No retirement communities import found')
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
                        ->title('Retirement Communities Import Started')
                        ->body('The retirement communities import has been queued and will start processing shortly.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Run Retirement Communities Import')
                ->modalDescription('This will start the retirement communities import process.')
                ->modalSubmitActionLabel('Start Import')
                ->visible(fn () => Import::where('provider_type', 'retirement_communities')->exists()),
        ];
    }
}
