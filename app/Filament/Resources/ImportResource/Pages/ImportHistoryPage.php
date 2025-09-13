<?php

namespace App\Filament\Resources\ImportResource\Pages;

use App\Filament\Resources\ImportResource;
use App\Jobs\ProcessImportJob;
use App\Models\ImportHistory;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImportHistoryPage extends ManageRelatedRecords
{
    protected static string $resource = ImportResource::class;

    protected static string $relationship = 'importHistories';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ImportHistory $record): string => $record->status_badge_color),
                TextColumn::make('duration')
                    ->getStateUsing(fn (ImportHistory $record): string => $record->duration ?? 'Running...'),
                TextColumn::make('records_processed')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('records_created')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('records_updated')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('records_skipped')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('records_missing')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('error_message')
                    ->limit(50)
                    ->color('danger')
                    ->visible(fn (ImportHistory $record): bool => ! empty($record->error_message)),
            ])
            ->defaultSort('started_at', 'desc')
            ->striped();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('run_import')
                ->label('Run Import Now')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->action(function () {
                    $import = $this->getOwnerRecord();
                    if ($import->canRunImport()) {
                        ProcessImportJob::dispatch($import);
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Run Import')
                ->modalDescription(fn (): string => "Are you sure you want to run the import for {$this->getOwnerRecord()->name}?")
                ->modalSubmitActionLabel('Run Import'),
        ];
    }
}
