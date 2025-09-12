<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DentistProviderResource\Pages;
use App\Models\Provider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DentistProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Dentists';

    protected static ?string $modelLabel = 'Dentist';

    protected static ?string $pluralModelLabel = 'Dentists';

    protected static ?string $navigationGroup = 'Providers';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('provider_id')
                    ->maxLength(255)
                    ->placeholder('External provider ID'),
                Forms\Components\TextInput::make('slug')
                    ->maxLength(255)
                    ->placeholder('URL-friendly slug')
                    ->required(),
                Forms\Components\FileUpload::make('photo')
                    ->label('Photo')
                    ->image()
                    ->directory('enhanced_photos/dentists')
                    ->visibility('public')
                    ->disk('public')
                    ->afterStateUpdated(function ($state, $record) {
                        if ($state && $record) {
                            $record->meta['enhanced_photo_path'] = '/storage/'.$state;
                            $record->save();
                        }
                    }),
                Forms\Components\RichEditor::make('enhanced_profile_text')
                    ->label('Enhanced Profile Text')
                    ->columnSpanFull()
                    ->helperText('Rich text content for the dentist profile'),
                Forms\Components\TextInput::make('enhanced_profile_text_path')
                    ->label('RTF File Path')
                    ->disabled()
                    ->helperText('Path to the downloaded RTF file'),
                Forms\Components\TextInput::make('latitude')
                    ->label('Latitude')
                    ->disabled()
                    ->helperText('Geocoded latitude coordinate'),
                Forms\Components\TextInput::make('longitude')
                    ->label('Longitude')
                    ->disabled()
                    ->helperText('Geocoded longitude coordinate'),
                Forms\Components\ViewField::make('map')
                    ->label('Location Map')
                    ->view('filament.components.provider-map-simple')
                    ->viewData(function ($record) {
                        if (! $record) {
                            return [
                                'latitude' => null,
                                'longitude' => null,
                                'address' => 'No record available',
                                'mapId' => 'map-no-record',
                            ];
                        }

                        $latitude = $record->meta['latitude'] ?? null;
                        $longitude = $record->meta['longitude'] ?? null;

                        if (! $latitude || ! $longitude) {
                            return [
                                'latitude' => null,
                                'longitude' => null,
                                'address' => 'No location data available - address geocoding required',
                                'mapId' => 'map-no-coords',
                            ];
                        }

                        $street = $record->meta['address-street'] ?? '';
                        $city = $record->meta['address-city'] ?? '';
                        $state = $record->meta['address-state'] ?? '';
                        $zip = $record->meta['address-zip'] ?? '';

                        $address = trim($street).' '.trim($city).' '.trim($state);
                        if (! empty($zip)) {
                            $address .= ' '.trim($zip);
                        }

                        return [
                            'latitude' => $latitude,
                            'longitude' => $longitude,
                            'address' => $address,
                            'mapId' => 'map-'.$record->id,
                        ];
                    })
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('meta')
                    ->keyLabel('Key')
                    ->valueLabel('Value')
                    ->columnSpanFull()
                    ->helperText('Additional metadata for this dentist'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->where('type', 'dentists');
            })
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
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Name')
                    ->getStateUsing(fn (Provider $record): string => $record->meta['title'] ?? 'Untitled')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('meta->title', 'like', "%{$search}%");
                    })
                    ->sortable()
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListDentistProviders::route('/'),
            'create' => Pages\CreateDentistProvider::route('/create'),
            'edit' => Pages\EditDentistProvider::route('/{record}/edit'),
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
        return static::getModel()::where('type', 'dentists')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }
}
