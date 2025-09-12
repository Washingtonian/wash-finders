<?php

namespace App\Filament\Resources\WeddingVendorProviderResource\Pages;

use App\Filament\Resources\WeddingVendorProviderResource;
use App\Models\Import;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWeddingVendorProviders extends ListRecords
{
    protected static string $resource = WeddingVendorProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('run_wedding_vendors_import')
                ->label('Run Wedding Vendors Import')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->action(function () {
                    $import = Import::where('provider_type', 'wedding_vendors')->first();

                    if (! $import) {
                        \Filament\Notifications\Notification::make()
                            ->title('No wedding vendors import found')
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
                        ->title('Wedding Vendors Import Started')
                        ->body('The wedding vendors import has been queued and will start processing shortly.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Run Wedding Vendors Import')
                ->modalDescription('This will start the wedding vendors import process.')
                ->modalSubmitActionLabel('Start Import')
                ->visible(fn () => Import::where('provider_type', 'wedding_vendors')->exists()),
        ];
    }
}
