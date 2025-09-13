<?php

namespace App\Filament\Resources\IndustryLeaderProviderResource\Pages;

use App\Filament\Resources\IndustryLeaderProviderResource;
use App\Jobs\ProcessImportJob;
use App\Models\Import;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListIndustryLeaderProviders extends ListRecords
{
    protected static string $resource = IndustryLeaderProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            Action::make('run_industry_leaders_import')
                ->label('Run Industry Leaders Import')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->action(function () {
                    $import = Import::where('provider_type', 'industry_leaders')->first();

                    if (! $import) {
                        Notification::make()
                            ->title('No industry leaders import found')
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
                        ->title('Industry Leaders Import Started')
                        ->body('The industry leaders import has been queued and will start processing shortly.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Run Industry Leaders Import')
                ->modalDescription('This will start the industry leaders import process.')
                ->modalSubmitActionLabel('Start Import')
                ->visible(fn () => Import::where('provider_type', 'industry_leaders')->exists()),
        ];
    }
}
