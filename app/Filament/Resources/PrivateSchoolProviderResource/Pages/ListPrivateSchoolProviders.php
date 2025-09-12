<?php

namespace App\Filament\Resources\PrivateSchoolProviderResource\Pages;

use App\Filament\Resources\PrivateSchoolProviderResource;
use App\Models\Import;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPrivateSchoolProviders extends ListRecords
{
    protected static string $resource = PrivateSchoolProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('run_private_schools_import')
                ->label('Run Private Schools Import')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->action(function () {
                    $import = Import::where('provider_type', 'private_schools')->first();

                    if (! $import) {
                        \Filament\Notifications\Notification::make()
                            ->title('No private schools import found')
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
                        ->title('Private Schools Import Started')
                        ->body('The private schools import has been queued and will start processing shortly.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Run Private Schools Import')
                ->modalDescription('This will start the private schools import process.')
                ->modalSubmitActionLabel('Start Import')
                ->visible(fn () => Import::where('provider_type', 'private_schools')->exists()),
        ];
    }
}
