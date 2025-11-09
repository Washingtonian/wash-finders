<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProviderResource\Pages;
use App\Jobs\ProcessImportJob;
use App\Jobs\ResetProvidersJob;
use App\Models\Import;
use App\Models\Provider;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use UnitEnum;

class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'All Providers';

    protected static ?string $modelLabel = 'Provider';

    protected static ?string $pluralModelLabel = 'Providers';

    protected static UnitEnum|string|null $navigationGroup = 'Manage';

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = false;

    // Remove dynamic navigation items - now handled by separate resources
    public static function getNavigationItems(): array
    {
        return [];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('type')
                    ->required()
                    ->options(Provider::getAvailableTypes())
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn (Set $set, $state) => $set('slug', Str::slug($state.'-'.now()->timestamp))
                    ),
                TextInput::make('provider_id')
                    ->maxLength(255)
                    ->placeholder('External provider ID'),
                TextInput::make('slug')
                    ->maxLength(255)
                    ->placeholder('URL-friendly slug')
                    ->required(),
                KeyValue::make('meta')
                    ->keyLabel('Key')
                    ->valueLabel('Value')
                    ->columnSpanFull()
                    ->helperText('Additional metadata for this provider'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Filter by type if specified in URL - this persists across all table operations
                $type = request()->get('type');
                if ($type) {
                    $query->where('type', $type);
                }
            })
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->headerActions([
                Action::make('import_status')
                    ->label(function () {
                        $runningCount = \App\Models\Import::where('last_run_status', 'running')->count();
                        $completedCount = \App\Models\Import::where('last_run_status', 'completed')->count();
                        $pendingCount = \App\Models\Import::where('last_run_status', 'pending')->count();
                        $failedCount = \App\Models\Import::where('last_run_status', 'failed')->count();

                        if ($runningCount > 0) {
                            return "Running: {$runningCount}";
                        } elseif ($completedCount > 0) {
                            return "Completed: {$completedCount}";
                        } elseif ($pendingCount > 0) {
                            return "Pending: {$pendingCount}";
                        } elseif ($failedCount > 0) {
                            return "Failed: {$failedCount}";
                        }

                        return 'No Imports';
                    })
                    ->icon(function () {
                        $runningCount = \App\Models\Import::where('last_run_status', 'running')->count();
                        $completedCount = \App\Models\Import::where('last_run_status', 'completed')->count();
                        $failedCount = \App\Models\Import::where('last_run_status', 'failed')->count();

                        if ($runningCount > 0) {
                            return 'heroicon-o-arrow-path';
                        }
                        if ($completedCount > 0) {
                            return 'heroicon-o-check-circle';
                        }
                        if ($failedCount > 0) {
                            return 'heroicon-o-x-circle';
                        }

                        return 'heroicon-o-clock';
                    })
                    ->color(function () {
                        $runningCount = \App\Models\Import::where('last_run_status', 'running')->count();
                        $completedCount = \App\Models\Import::where('last_run_status', 'completed')->count();
                        $failedCount = \App\Models\Import::where('last_run_status', 'failed')->count();

                        if ($runningCount > 0) {
                            return 'warning';
                        }
                        if ($completedCount > 0) {
                            return 'success';
                        }
                        if ($failedCount > 0) {
                            return 'danger';
                        }

                        return 'gray';
                    })
                    ->badge(function () {
                        $runningCount = \App\Models\Import::where('last_run_status', 'running')->count();
                        $completedCount = \App\Models\Import::where('last_run_status', 'completed')->count();
                        $pendingCount = \App\Models\Import::where('last_run_status', 'pending')->count();
                        $failedCount = \App\Models\Import::where('last_run_status', 'failed')->count();

                        return $runningCount + $completedCount + $pendingCount + $failedCount;
                    })
                    ->badgeColor(function () {
                        $runningCount = Import::where('last_run_status', 'running')->count();
                        $completedCount = Import::where('last_run_status', 'completed')->count();
                        $failedCount = Import::where('last_run_status', 'failed')->count();

                        if ($runningCount > 0) {
                            return 'warning';
                        }
                        if ($completedCount > 0) {
                            return 'success';
                        }
                        if ($failedCount > 0) {
                            return 'danger';
                        }

                        return 'gray';
                    })
                    ->tooltip(function () {
                        $runningImports = Import::where('last_run_status', 'running')->get(['name', 'provider_type', 'last_run_at']);
                        $completedImports = Import::where('last_run_status', 'completed')->get(['name', 'provider_type', 'last_run_at']);
                        $pendingImports = Import::where('last_run_status', 'pending')->get(['name', 'provider_type']);
                        $failedImports = Import::where('last_run_status', 'failed')->get(['name', 'provider_type', 'last_run_at']);

                        $tooltip = '';

                        if ($runningImports->count() > 0) {
                            $tooltip .= "Running Imports:\n";
                            foreach ($runningImports as $import) {
                                $tooltip .= "• {$import->name} ({$import->provider_type})\n";
                            }
                        }

                        if ($completedImports->count() > 0) {
                            $tooltip .= "\nCompleted Imports:\n";
                            foreach ($completedImports as $import) {
                                $tooltip .= "• {$import->name} ({$import->provider_type})\n";
                            }
                        }

                        if ($pendingImports->count() > 0) {
                            $tooltip .= "\nPending Imports:\n";
                            foreach ($pendingImports as $import) {
                                $tooltip .= "• {$import->name} ({$import->provider_type})\n";
                            }
                        }

                        if ($failedImports->count() > 0) {
                            $tooltip .= "\nFailed Imports:\n";
                            foreach ($failedImports as $import) {
                                $tooltip .= "• {$import->name} ({$import->provider_type})\n";
                            }
                        }

                        return $tooltip ?: 'No imports found';
                    })
                    ->disabled()
                    ->extraAttributes(['class' => 'cursor-default']),

                Action::make('run_imports')
                    ->label('Run Imports')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->schema([
                        Select::make('import_id')
                            ->label('Select Import to Run')
                            ->options(function () {
                                return Import::active()
                                    ->get()
                                    ->mapWithKeys(fn ($import) => [
                                        $import->id => "{$import->name} ({$import->provider_type})".
                                            ($import->last_run_status === 'running' ? ' [RUNNING]' : ''),
                                    ]);
                            })
                            ->required()
                            ->searchable()
                            ->placeholder('Choose an import to run'),
                        Toggle::make('force_run')
                            ->label('Force Run (even if already running)')
                            ->default(false)
                            ->helperText('Check this to run even if the import is currently running'),
                    ])
                    ->action(function (array $data) {
                        $import = Import::find($data['import_id']);

                        if (! $import) {
                            Notification::make()
                                ->title('Import not found')
                                ->danger()
                                ->send();

                            return;
                        }

                        if (! $data['force_run'] && $import->last_run_status === 'running') {
                            Notification::make()
                                ->title('Import already running')
                                ->body('This import is already running. Use "Force Run" to override.')
                                ->warning()
                                ->send();

                            return;
                        }

                        // Dispatch the import job to database queue
                        ProcessImportJob::dispatch($import);

                        // Update import status
                        $import->update([
                            'last_run_status' => 'running',
                            'last_run_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Import Started')
                            ->body("Import '{$import->name}' has been queued and will start processing shortly.")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Run Import')
                    ->modalDescription('Select an import to run. This will queue the import job for processing.')
                    ->modalSubmitActionLabel('Start Import')
                    ->visible(fn () => Import::active()->count() > 0),

                Action::make('run_current_type_import')
                    ->label(function () {
                        $type = request()->get('type');
                        if (! $type) {
                            return 'Run All Imports';
                        }

                        $importCount = Import::active()
                            ->where('provider_type', $type)
                            ->count();

                        return "Run {$type} Imports ({$importCount})";
                    })
                    ->icon('heroicon-o-bolt')
                    ->color('primary')
                    ->action(function () {
                        $type = request()->get('type');

                        // Always filter by type if it's provided
                        $query = Import::active();

                        if ($type) {
                            $query->where('provider_type', $type);
                        }

                        $imports = $query->get();

                        if ($imports->isEmpty()) {
                            $message = $type
                                ? "No active {$type} imports are available to run."
                                : 'No active imports are available to run.';

                            Notification::make()
                                ->title('No imports available')
                                ->body($message)
                                ->warning()
                                ->send();

                            return;
                        }

                        $startedCount = 0;
                        $importNames = [];

                        foreach ($imports as $import) {
                            // Dispatch the import job to database queue
                            ProcessImportJob::dispatch($import);

                            // Update import status
                            $import->update([
                                'last_run_status' => 'running',
                                'last_run_at' => now(),
                            ]);

                            $importNames[] = $import->name;
                            $startedCount++;
                        }

                        $message = $type
                            ? "Started {$startedCount} {$type} import(s): ".implode(', ', $importNames)
                            : "Started {$startedCount} import(s) for processing.";

                        Notification::make()
                            ->title('Imports Started')
                            ->body($message)
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading(function () {
                        $type = request()->get('type');

                        return $type ? "Run {$type} Imports" : 'Run All Imports';
                    })
                    ->modalDescription(function () {
                        $type = request()->get('type');
                        if ($type) {
                            $count = Import::active()
                                ->where('provider_type', $type)
                                ->count();

                            return "This will start all available {$type} imports ({$count} import(s)).";
                        } else {
                            $count = Import::active()
                                ->count();

                            return "This will start all available imports ({$count} import(s)).";
                        }
                    })
                    ->modalSubmitActionLabel('Start Imports')
                    ->visible(function () {
                        $type = request()->get('type');

                        if ($type) {
                            return Import::active()
                                ->where('provider_type', $type)
                                ->count() > 0;
                        } else {
                            return Import::active()
                                ->count() > 0;
                        }
                    }),

                Action::make('reset_and_resync')
                    ->label('Reset & Resync')
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->schema([
                        Select::make('provider_type')
                            ->label('Provider Type')
                            ->options(function () {
                                return collect(['all' => 'All Provider Types'])
                                    ->merge(Provider::getAvailableTypes())
                                    ->toArray();
                            })
                            ->default(fn () => request()->get('type') ?? 'all')
                            ->required(),
                        Toggle::make('confirm_reset')
                            ->label('I understand this will permanently delete existing providers before importing fresh data.')
                            ->helperText('All providers for the selected type will be deleted before the spreadsheet is re-imported.')
                            ->default(false),
                    ])
                    ->action(function (array $data) {
                        if (! ($data['confirm_reset'] ?? false)) {
                            Notification::make()
                                ->title('Reset not confirmed')
                                ->body('Please confirm the reset before continuing.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $type = $data['provider_type'] ?? 'all';

                        ResetProvidersJob::dispatch($type === 'all' ? null : $type);

                        $label = $type === 'all'
                            ? 'all provider types'
                            : Provider::getTypeLabel($type);

                        Notification::make()
                            ->title('Reset queued')
                            ->body("A reset and resync job has been queued for {$label}.")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Reset Providers & Resync')
                    ->modalDescription('This will permanently delete the selected providers and queue a fresh import from the spreadsheet.')
                    ->modalSubmitActionLabel('Queue Reset')
                    ->visible(fn () => Import::count() > 0 || ! empty(config('providers'))),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'dentists' => 'info',
                        'doctors' => 'danger',
                        'financal_advisors' => 'success',
                        'home_resources' => 'warning',
                        'industry_leaders' => 'primary',
                        'lawyers' => 'gray',
                        'mortgage_professionals' => 'info',
                        'pet_vendors' => 'success',
                        'private_schools' => 'warning',
                        'realtors' => 'primary',
                        'rental_properties' => 'success',
                        'retirement_communities' => 'info',
                        'wedding_vendors' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Provider::getTypeLabel($state))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('provider_id')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                TextColumn::make('meta_count')
                    ->label('Meta Fields')
                    ->getStateUsing(fn (Provider $record): int => $record->meta->count())
                    ->badge()
                    ->color('gray'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(Provider::getAvailableTypes()),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProviders::route('/'),
            'create' => Pages\CreateProvider::route('/create'),
            'view' => Pages\ViewProvider::route('/{record}'),
            'edit' => Pages\EditProvider::route('/{record}/edit'),
        ];
    }

    public static function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        // Preserve the 'type' parameter when generating URLs
        $type = request()->get('type');
        if ($type && ! isset($parameters['type'])) {
            $parameters['type'] = $type;
        }

        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant);
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
