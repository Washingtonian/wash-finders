<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IndustryLeaderProviderResource\Pages\CreateIndustryLeaderProvider;
use App\Filament\Resources\IndustryLeaderProviderResource\Pages\EditIndustryLeaderProvider;
use App\Filament\Resources\IndustryLeaderProviderResource\Pages\ListIndustryLeaderProviders;
use App\Models\Provider;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class IndustryLeaderProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Industry Leaders';

    protected static ?string $modelLabel = 'Industry Leader';

    protected static ?string $pluralModelLabel = 'Industry Leaders';

    protected static UnitEnum|string|null $navigationGroup = 'Providers';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('provider_id')
                    ->maxLength(255)
                    ->placeholder('External provider ID'),
                TextInput::make('slug')
                    ->maxLength(255)
                    ->placeholder('URL-friendly slug')
                    ->required(),
                FileUpload::make('photo')
                    ->label('Photo')
                    ->image()
                    ->directory('enhanced_photos/industry_leaders')
                    ->visibility('public'),
                KeyValue::make('meta')
                    ->keyLabel('Key')
                    ->valueLabel('Value')
                    ->columnSpanFull()
                    ->addActionLabel('Add Field'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'industry_leaders'))
            ->columns([
                ImageColumn::make('photo')
                    ->label('Photo')
                    ->getStateUsing(function (Provider $record): ?string {
                        $path = $record->meta['enhanced_photo_path'] ?? null;
                        if (! $path) {
                            return null;
                        }

                        // Remove leading slash if present and ensure it starts with /storage/
                        $path = ltrim($path, '/');
                        if (! str_starts_with($path, 'storage/')) {
                            $path = 'storage/'.ltrim($path, 'storage/');
                        }

                        // Check if file actually exists on disk before showing it
                        $fullPath = public_path($path);
                        if (! file_exists($fullPath)) {
                            return null;
                        }

                        return url($path);
                    })
                    ->size(60)
                    ->circular()
                    ->defaultImageUrl(null), // Don't show placeholder for missing photos
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('provider_id')
                    ->getStateUsing(fn (Provider $record): string => $record->meta['id'] ?? $record->provider_id ?? '')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('meta->id', 'like', "%{$search}%")
                            ->orWhere('provider_id', 'like', "%{$search}%");
                    })
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Name')
                    ->getStateUsing(fn (Provider $record): string => $record->meta['title'] ?? 'Untitled')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('meta->title', 'like', "%{$search}%");
                    })
                    ->sortable()
                    ->limit(30),
                TextColumn::make('business_name')
                    ->label('Business')
                    ->getStateUsing(fn (Provider $record): string => $record->meta['business_name'] ?? '')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('meta->business_name', 'like', "%{$search}%");
                    })
                    ->limit(30),
                TextColumn::make('specialty')
                    ->label('Specialty')
                    ->getStateUsing(fn (Provider $record): string => $record->meta['specialty'] ?? '')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('meta->specialty', 'like', "%{$search}%");
                    })
                    ->limit(20),
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
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession();
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
            'index' => ListIndustryLeaderProviders::route('/'),
            'create' => CreateIndustryLeaderProvider::route('/create'),
            'edit' => EditIndustryLeaderProvider::route('/{record}/edit'),
        ];
    }

    public static function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        $parameters['type'] = 'industry_leaders';

        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('type', 'industry_leaders')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }
}
