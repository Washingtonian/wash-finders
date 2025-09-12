<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DoctorProviderResource\Pages;
use App\Models\Provider;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DoctorProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Doctors';

    protected static ?string $modelLabel = 'Doctor';

    protected static ?string $pluralModelLabel = 'Doctors';

    protected static UnitEnum|string|null $navigationGroup = 'Providers';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('provider_id')
                    ->maxLength(255)
                    ->placeholder('External provider ID'),
                Filament\Schemas\Components\TextInput::make('slug')
                    ->maxLength(255)
                    ->placeholder('URL-friendly slug')
                    ->required(),
                Filament\Schemas\Components\FileUpload::make('photo')
                    ->label('Photo')
                    ->image()
                    ->directory('enhanced_photos/doctors')
                    ->visibility('public'),
                Filament\Schemas\Components\TextInput::make('latitude')
                    ->label('Latitude')
                    ->disabled()
                    ->helperText('Geocoded latitude coordinate'),
                Filament\Schemas\Components\TextInput::make('longitude')
                    ->label('Longitude')
                    ->disabled()
                    ->helperText('Geocoded longitude coordinate'),
                Filament\Schemas\Components\View::make('filament.components.provider-map')
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
                Filament\Schemas\Components\KeyValue::make('meta')
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
                Tables\Columns\ImageColumn::make('photo')
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
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('provider_id')
                    ->getStateUsing(fn (Provider $record): string => $record->meta['id'] ?? $record->provider_id ?? '')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('meta->id', 'like', "%{$search}%")
                            ->orWhere('provider_id', 'like', "%{$search}%");
                    }),
                Tables\Columns\TextColumn::make('title')
                    ->label('Name')
                    ->getStateUsing(fn (Provider $record): string => $record->meta['title'] ?? 'Untitled')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('meta->title', 'like', "%{$search}%");
                    })
                    ->limit(30),
                Tables\Columns\TextColumn::make('business_name')
                    ->label('Business')
                    ->getStateUsing(fn (Provider $record): string => $record->meta['business_name'] ?? '')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('meta->business_name', 'like', "%{$search}%");
                    })
                    ->limit(30),
                Tables\Columns\TextColumn::make('specialty')
                    ->label('Specialty')
                    ->getStateUsing(fn (Provider $record): string => $record->meta['specialty'] ?? '')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('meta->specialty', 'like', "%{$search}%");
                    })
                    ->limit(20),
                Tables\Columns\TextColumn::make('meta_count')
                    ->label('Meta Fields')
                    ->getStateUsing(fn (Provider $record): int => $record->meta->count())
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
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
            'index' => Pages\ListDoctorProviders::route('/'),
            'create' => Pages\CreateDoctorProvider::route('/create'),
            'edit' => Pages\EditDoctorProvider::route('/{record}/edit'),
        ];
    }

    public static function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null, bool $shouldGuessMissingParameters = false): string
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
