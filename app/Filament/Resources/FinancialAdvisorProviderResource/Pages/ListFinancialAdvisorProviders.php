<?php

namespace App\Filament\Resources\FinancialAdvisorProviderResource\Pages;

use App\Filament\Resources\FinancialAdvisorProviderResource;
use App\Jobs\ProcessImportJob;
use App\Models\Import;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListFinancialAdvisorProviders extends ListRecords
{
    protected static string $resource = FinancialAdvisorProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            Action::make('run_financal_advisors_import')
                ->label('Run Financal Advisors Import')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->action(function () {
                    $import = Import::where('provider_type', 'financal_advisors')->first();

                    if (! $import) {
                        Notification::make()
                            ->title('No financal advisors import found')
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
                        ->title('Financal Advisors Import Started')
                        ->body('The financal advisors import has been queued and will start processing shortly.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Run Financal Advisors Import')
                ->modalDescription('This will start the financal advisors import process.')
                ->modalSubmitActionLabel('Start Import')
                ->visible(fn () => Import::where('provider_type', 'financal_advisors')->exists()),
        ];
    }
}
