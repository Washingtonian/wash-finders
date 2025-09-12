<?php

namespace App\Filament\Resources\ImportResource\Pages;

use App\Filament\Resources\ImportResource;
use App\Models\Import;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;

class VersionHistory extends ListRecords
{
    protected static string $resource = ImportResource::class;

    protected string $view = 'filament.resources.imports.version-history';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Import::query()
                    ->where('provider_type', request()->get('provider_type'))
                    ->orderBy('version', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('version')
                    ->badge()
                    ->color(fn (Import $record): string => $record->is_current_version ? 'success' : 'gray')
                    ->formatStateUsing(fn (Import $record): string => $record->is_current_version ? "v{$record->version} (Current)" : "v{$record->version}")
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('csv_url')
                    ->label('CSV URL')
                    ->limit(60)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 60) {
                            return $state;
                        }

                        return null;
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_run_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never run'),
                Tables\Columns\TextColumn::make('last_run_status')
                    ->badge()
                    ->color(fn (Import $record): string => $record->status_badge_color)
                    ->formatStateUsing(fn (Import $record): string => $record->status_label)
                    ->placeholder('Never run'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('set_current')
                    ->label('Set as Current')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Import $record): bool => ! $record->is_current_version)
                    ->action(function (Import $record) {
                        $record->markAsCurrentVersion();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Set as Current Version')
                    ->modalDescription("Are you sure you want to set version {$record->version} as the current version?")
                    ->modalSubmitActionLabel('Set Current'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back_to_imports')
                ->label('Back to All Imports')
                ->icon('heroicon-o-arrow-left')
                ->url(route('filament.admin.resources.imports.index')),
        ];
    }
}
