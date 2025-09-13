<?php

namespace App\Filament\Resources\MortgageProfessionalProviderResource\Pages;

use App\Filament\Resources\MortgageProfessionalProviderResource;
use App\Jobs\ProcessImportJob;
use App\Models\Import;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListMortgageProfessionalProviders extends ListRecords
{
    protected static string $resource = MortgageProfessionalProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            Action::make('run_mortgage_professionals_import')
                ->label('Run Mortgage Professionals Import')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->action(function () {
                    $import = Import::where('provider_type', 'mortgage_professionals')->first();

                    if (! $import) {
                        Notification::make()
                            ->title('No mortgage professionals import found')
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
                        ->title('Mortgage Professionals Import Started')
                        ->body('The mortgage professionals import has been queued and will start processing shortly.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Run Mortgage Professionals Import')
                ->modalDescription('This will start the mortgage professionals import process.')
                ->modalSubmitActionLabel('Start Import')
                ->visible(fn () => Import::where('provider_type', 'mortgage_professionals')->exists()),
        ];
    }
}
