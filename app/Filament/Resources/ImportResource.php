<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ImportResource\Pages\CreateImport;
use App\Filament\Resources\ImportResource\Pages\EditImport;
use App\Filament\Resources\ImportResource\Pages\ImportHistoryPage;
use App\Filament\Resources\ImportResource\Pages\ImportScheduling;
use App\Filament\Resources\ImportResource\Pages\ListImports;
use App\Filament\Resources\ImportResource\Pages\VersionHistory;
use App\Filament\Resources\ImportResource\Pages\ViewImport;
use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Models\Provider;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use UnitEnum;

class ImportResource extends Resource
{
    protected static ?string $model = Import::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-arrow-down';

    protected static ?string $navigationLabel = 'Import Management';

    protected static ?string $modelLabel = 'Import';

    protected static ?string $pluralModelLabel = 'Imports';

    protected static UnitEnum|string|null $navigationGroup = 'Manage';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Dentists (2019+)'),
                        Select::make('provider_type')
                            ->required()
                            ->options(Provider::getAvailableTypes())
                            ->searchable()
                            ->placeholder('Select provider type'),
                        Textarea::make('description')
                            ->placeholder('Optional description of this import')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('CSV Configuration')
                    ->schema([
                        TextInput::make('csv_url')
                            ->required()
                            ->url()
                            ->placeholder('https://docs.google.com/spreadsheets/...')
                            ->helperText('Google Sheets URL or direct CSV download link')
                            ->suffixIcon('heroicon-o-clipboard')
                            ->suffixIconColor('gray')
                            ->extraAttributes([
                                'x-data' => '{}',
                                'x-on:click.suffix' => 'navigator.clipboard.writeText($el.closest(\'[wire\\:id]\').querySelector(\'input\').value).then(() => { $dispatch(\'notify\', { type: \'success\', message: \'URL copied\' }) })',
                            ]),
                        TextInput::make('version')
                            ->required()
                            ->default('1.0')
                            ->placeholder('1.0, 1.1, 2.0, etc.')
                            ->helperText('Version number for tracking different iterations'),
                        Toggle::make('is_current_version')
                            ->label('Set as Current Version')
                            ->helperText('Only one version per provider type can be current')
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state, $get) {
                                if ($state) {
                                    $set('is_active', true);
                                }
                            }),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Inactive imports cannot be run'),
                    ])
                    ->columns(2),

                Section::make('Import Settings')
                    ->schema([
                        KeyValue::make('import_settings')
                            ->keyLabel('Setting')
                            ->valueLabel('Value')
                            ->columnSpanFull()
                            ->helperText('Additional import configuration options'),
                        KeyValue::make('mapping_config')
                            ->keyLabel('CSV Column')
                            ->valueLabel('Provider Field')
                            ->columnSpanFull()
                            ->helperText('Map CSV columns to provider fields'),
                    ]),

                Section::make('Scheduling')
                    ->description('Configure automatic import scheduling')
                    ->schema([
                        Toggle::make('schedule_enabled')
                            ->label('Enable Scheduling')
                            ->helperText('Turn on automatic scheduling for this import')
                            ->live()
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                Select::make('schedule_frequency')
                                    ->label('Schedule Frequency')
                                    ->options([
                                        'daily' => 'Daily',
                                        'weekly' => 'Weekly',
                                        'monthly' => 'Monthly',
                                    ])
                                    ->visible(fn (Get $get): bool => $get('schedule_enabled'))
                                    ->required(fn (Get $get): bool => $get('schedule_enabled'))
                                    ->default('weekly')
                                    ->live(),

                                TimePicker::make('schedule_time')
                                    ->label('Run Time')
                                    ->visible(fn (Get $get): bool => $get('schedule_enabled'))
                                    ->required(fn (Get $get): bool => $get('schedule_enabled'))
                                    ->default('08:00')
                                    ->seconds(false)
                                    ->format('H:i'),
                            ]),

                        CheckboxList::make('schedule_days')
                            ->label('Days of Week')
                            ->options([
                                'monday' => 'Monday',
                                'tuesday' => 'Tuesday',
                                'wednesday' => 'Wednesday',
                                'thursday' => 'Thursday',
                                'friday' => 'Friday',
                                'saturday' => 'Saturday',
                                'sunday' => 'Sunday',
                            ])
                            ->visible(fn (Get $get): bool => $get('schedule_enabled') && $get('schedule_frequency') === 'weekly')
                            ->columns(7)
                            ->columnSpanFull()
                            ->default(['monday']),
                    ])
                    ->compact()
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->label('Import Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('sm')
                    ->description(fn (Import $record): string => $record->description ? Str::limit($record->description, 80) : '')
                    ->tooltip(fn (Import $record): ?string => $record->description ?: null),

                TextColumn::make('provider_type')
                    ->label('Category')
                    ->badge()
                    ->color(function (string $state): string {
                        $availableTypes = Provider::getAvailableTypes();
                        $colors = ['info', 'warning', 'success', 'danger', 'gray', 'primary', 'secondary'];
                        $typeIndex = array_search($state, array_keys($availableTypes));

                        return $colors[$typeIndex % count($colors)] ?? 'gray';
                    })
                    ->formatStateUsing(fn (string $state): string => Provider::getTypeLabel($state))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('version')
                    ->badge()
                    ->color(fn (Import $record): string => $record->is_current_version ? 'success' : 'gray')
                    ->formatStateUsing(fn (Import $record): string => $record->is_current_version ? "v{$record->version} (Current)" : "v{$record->version}")
                    ->sortable()
                    ->width('120px'),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->sortable()
                    ->width('80px'),

                TextColumn::make('last_run_summary')
                    ->label('Last Run')
                    ->getStateUsing(function (Import $record): string {
                        try {
                            if (! $record->last_run_at) {
                                return 'Never run';
                            }

                            $summary = $record->last_run_summary ?? 'No details available';

                            return $summary;
                        } catch (\Exception $e) {
                            return 'Error loading summary';
                        }
                    })
                    ->limit(60)
                    ->tooltip(function (Import $record): ?string {
                        if (! $record->last_run_at) {
                            return null;
                        }

                        return $record->last_run_summary ?? 'No details available';
                    })
                    ->color(fn (Import $record): ?string => $record->last_run_status === 'failed' ? 'danger' : null)
                    ->wrap(false)
                    ->size('sm'),

                TextColumn::make('records_count')
                    ->label('Records')
                    ->getStateUsing(function (Import $record): string {
                        $created = $record->records_created ?? 0;
                        $updated = $record->records_updated ?? 0;
                        $total = $created + $updated;

                        if ($total === 0) {
                            return '-';
                        }

                        $parts = [];
                        if ($created > 0) {
                            $parts[] = "{$created} new";
                        }
                        if ($updated > 0) {
                            $parts[] = "{$updated} updated";
                        }

                        return implode(', ', $parts);
                    })
                    ->badge()
                    ->color('info')
                    ->width('120px')
                    ->size('sm'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->size('sm'),

                TextColumn::make('updated_at')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->size('sm'),
            ])
            ->filters([
                SelectFilter::make('provider_type')
                    ->options(Provider::getAvailableTypes()),
                TernaryFilter::make('is_active')
                    ->label('Active Status'),
                TernaryFilter::make('is_current_version')
                    ->label('Current Version Only'),
                SelectFilter::make('version')
                    ->options(function () {
                        return Import::distinct()
                            ->whereNotNull('version')
                            ->pluck('version', 'version')
                            ->toArray();
                    })
                    ->multiple()
                    ->searchable(),
                SelectFilter::make('last_run_status')
                    ->options([
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'running' => 'Running',
                        'pending' => 'Pending',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('run_import')
                    ->label('Run Import')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->size('sm')
                    ->visible(fn (Import $record): bool => $record->canRunImport())
                    ->action(function (Import $record) {
                        // Dispatch the import job
                        ProcessImportJob::dispatch($record);

                        // Update import status to running
                        $record->update([
                            'last_run_status' => 'running',
                            'last_run_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Import Started')
                            ->body("Import '{$record->name}' has been queued and will start processing shortly.")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Run Import')
                    ->modalDescription(fn (Import $record): string => "Are you sure you want to run the import for {$record->name}?")
                    ->modalSubmitActionLabel('Run Import'),

                \Filament\Actions\Action::make('cancel_import')
                    ->label('Cancel')
                    ->icon('heroicon-o-stop')
                    ->color('danger')
                    ->size('sm')
                    ->visible(fn (Import $record): bool => $record->last_run_status === 'running')
                    ->action(function (Import $record) {
                        $record->update(['last_run_status' => 'completed']);
                        // TODO: Cancel running job
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Import')
                    ->modalDescription('Are you sure you want to cancel the running import?')
                    ->modalSubmitActionLabel('Cancel Import'),

                ActionGroup::make([
                    ViewAction::make()
                        ->label('View Details'),
                    EditAction::make()
                        ->label('Edit Configuration'),
                    Action::make('view_versions')
                        ->label('View All Versions')
                        ->icon('heroicon-o-code-bracket')
                        ->color('info')
                        ->url(function (Import $record): string {
                            return route('filament.admin.resources.imports.index', [
                                'tableFilters' => [
                                    'provider_type' => ['value' => $record->provider_type],
                                ],
                            ]);
                        }),
                    Action::make('scheduling_options')
                        ->label('Scheduling Options')
                        ->icon('heroicon-o-clock')
                        ->url(fn (Import $record): string => route('filament.admin.resources.imports.scheduling', $record)),
                    Action::make('history_logs')
                        ->label('View History')
                        ->icon('heroicon-o-document-text')
                        ->url(fn (Import $record): string => route('filament.admin.resources.imports.history', $record)),
                    DeleteAction::make()
                        ->label('Delete Import'),
                ])
                    ->label('More Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('version', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->poll('30s')
            ->reorderable('name');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImports::route('/'),
            'create' => CreateImport::route('/create'),
            'view' => ViewImport::route('/{record}'),
            'edit' => EditImport::route('/{record}/edit'),
            'scheduling' => ImportScheduling::route('/{record}/scheduling'),
            'history' => ImportHistoryPage::route('/{record}/history'),
            'version-history' => VersionHistory::route('/version-history'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
