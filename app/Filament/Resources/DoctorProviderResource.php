<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DoctorProviderResource\Pages\CreateDoctorProvider;
use App\Filament\Resources\DoctorProviderResource\Pages\EditDoctorProvider;
use App\Filament\Resources\DoctorProviderResource\Pages\ListDoctorProviders;
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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use UnitEnum;

class DoctorProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Doctors';

    protected static ?string $modelLabel = 'Doctor';

    protected static ?string $pluralModelLabel = 'Doctors';

    protected static UnitEnum|string|null $navigationGroup = 'Providers';

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
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
                    ->directory('enhanced_photos/doctors')
                    ->visibility('public'),
                TextInput::make('latitude')
                    ->label('Latitude')
                    ->disabled()
                    ->helperText('Geocoded latitude coordinate'),
                TextInput::make('longitude')
                    ->label('Longitude')
                    ->disabled()
                    ->helperText('Geocoded longitude coordinate'),
                View::make('filament.components.provider-map')
                    ->label('Location Map')
                    ->columnSpanFull()
                    ->viewData(function ($record) {
                        $address = null;
                        if ($record) {
                            $street = $record->meta['address-street'] ?? '';
                            $city = $record->meta['address-city'] ?? '';
                            $state = $record->meta['address-state'] ?? '';
                            $zip = $record->meta['address-zip'] ?? '';

                            if (! empty($street) && ! empty($city) && ! empty($state)) {
                                $address = trim($street).' '.trim($city).' '.trim($state);
                                if (! empty($zip)) {
                                    $address .= ' '.trim($zip);
                                }
                            }
                        }

                        return [
                            'latitude' => $record?->meta['latitude'] ?? null,
                            'longitude' => $record?->meta['longitude'] ?? null,
                            'address' => $address,
                        ];
                    }),
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
            ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'doctors'))
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
                    ->imageSize(60)
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
                    }),
                TextColumn::make('title')
                    ->label('Name')
                    ->getStateUsing(fn (Provider $record): string => $record->meta['title'] ?? 'Untitled')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('meta->title', 'like', "%{$search}%");
                    })
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
            ->toolbarActions([
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
            'index' => ListDoctorProviders::route('/'),
            'create' => CreateDoctorProvider::route('/create'),
            'edit' => EditDoctorProvider::route('/{record}/edit'),
        ];
    }

    public static function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        $parameters['type'] = 'doctors';

        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('type', 'doctors')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }
}
