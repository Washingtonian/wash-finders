<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DentistProviderResource\Pages;
use App\Models\Provider;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class DentistProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Dentists';

    protected static ?string $modelLabel = 'Dentist';

    protected static ?string $pluralModelLabel = 'Dentists';

    protected static UnitEnum|string|null $navigationGroup = 'Providers';

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        TextInput::make('provider_id')
                            ->maxLength(255)
                            ->placeholder('External provider ID'),
                        TextInput::make('slug')
                            ->maxLength(255)
                            ->placeholder('URL-friendly slug')
                            ->required(),
                        TextInput::make('title')
                            ->label('Name')
                            ->maxLength(255)
                            ->placeholder('Dentist name'),
                        TextInput::make('business_name')
                            ->label('Business Name')
                            ->maxLength(255)
                            ->placeholder('Practice or business name'),
                    ])
                    ->columns(2),

                Section::make('Contact Information')
                    ->schema([
                        TextInput::make('website')
                            ->label('Website')
                            ->url()
                            ->placeholder('https://example.com'),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->placeholder('dentist@example.com'),
                        TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->placeholder('(555) 123-4567'),
                    ])
                    ->columns(3),

                Section::make('Social Media')
                    ->schema([
                        TextInput::make('facebook_url')
                            ->label('Facebook URL')
                            ->url()
                            ->placeholder('https://facebook.com/username'),
                        TextInput::make('twitter_url')
                            ->label('Twitter URL')
                            ->url()
                            ->placeholder('https://twitter.com/username'),
                        TextInput::make('instagram_url')
                            ->label('Instagram URL')
                            ->url()
                            ->placeholder('https://instagram.com/username'),
                        TextInput::make('linkedin_url')
                            ->label('LinkedIn URL')
                            ->url()
                            ->placeholder('https://linkedin.com/in/username'),
                    ])
                    ->columns(2),

                Section::make('Professional Information')
                    ->schema([
                        TextInput::make('specialty')
                            ->label('Primary Specialty')
                            ->maxLength(255)
                            ->placeholder('General Dentistry, Orthodontics, etc.'),
                        Textarea::make('specialties')
                            ->label('All Specialties')
                            ->placeholder('List all specialties, one per line')
                            ->rows(3),
                        Toggle::make('best_of_washingtonian')
                            ->label('Best of Washingtonian')
                            ->helperText('Featured in Best of Washingtonian'),
                    ])
                    ->columns(2),

                Section::make('Location & Address')
                    ->schema([
                        TextInput::make('address_street')
                            ->label('Street Address')
                            ->placeholder('123 Main Street'),
                        TextInput::make('address_city')
                            ->label('City')
                            ->placeholder('Washington'),
                        TextInput::make('address_state')
                            ->label('State')
                            ->placeholder('DC'),
                        TextInput::make('address_zip')
                            ->label('ZIP Code')
                            ->placeholder('20001'),
                        TextInput::make('latitude')
                            ->label('Latitude')
                            ->disabled()
                            ->helperText('Geocoded latitude coordinate'),
                        TextInput::make('longitude')
                            ->label('Longitude')
                            ->disabled()
                            ->helperText('Geocoded longitude coordinate'),
                    ])
                    ->columns(3),

                Section::make('Images & Media')
                    ->schema([
                        FileUpload::make('photo')
                            ->label('Enhanced Photo')
                            ->image()
                            ->directory('photos/dentists')
                            ->visibility('public')
                            ->disk('public')
                            ->afterStateUpdated(function ($state, $record) {
                                if ($state && $record) {
                                    $record->meta['enhanced_photo_path'] = '/storage/'.$state;
                                    $record->save();
                                }
                            }),
                        FileUpload::make('additional_images')
                            ->label('Additional Images')
                            ->image()
                            ->multiple()
                            ->directory('enhanced_photos/dentists')
                            ->visibility('public')
                            ->disk('public')
                            ->default(function ($record) {
                                if ($record && isset($record->meta['extra-enhanced-photo-filenames'])) {
                                    $filenames = explode(',', $record->meta['extra-enhanced-photo-filenames']);
                                    $paths = [];
                                    foreach ($filenames as $filename) {
                                        $filename = trim($filename);
                                        if (! empty($filename)) {
                                            $fullPath = 'enhanced_photos/dentists/'.$filename;
                                            // Check if file actually exists
                                            if (Storage::disk('public')->exists($fullPath)) {
                                                $paths[] = $fullPath;
                                            }
                                        }
                                    }

                                    return $paths;
                                }

                                return [];
                            })
                            ->afterStateUpdated(function ($state, $record) {
                                if ($record && $state) {
                                    $filenames = [];
                                    foreach ($state as $path) {
                                        $filename = basename($path);
                                        $filenames[] = $filename;
                                    }
                                    $record->meta['extra-enhanced-photo-filenames'] = implode(',', $filenames);
                                    $record->save();
                                }
                            }),
                    ])
                    ->columns(2),

                Section::make('Content & Profile')
                    ->schema([
                        RichEditor::make('enhanced_profile_text')
                            ->label('Enhanced Profile Text')
                            ->columnSpanFull()
                            ->helperText('Rich text content for the dentist profile'),
                        TextInput::make('enhanced_profile_text_path')
                            ->label('RTF File Path')
                            ->disabled()
                            ->helperText('Path to the downloaded RTF file')
                            ->default(function ($record) {
                                if ($record && isset($record->meta['enhanced-profile-text-filename'])) {
                                    return $record->meta['enhanced-profile-text-filename'];
                                }

                                return null;
                            }),
                    ]),

                Section::make('Additional Data')
                    ->schema([
                        KeyValue::make('additional_data')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->helperText('Additional metadata fields'),
                    ]),

                Section::make('Location Map')
                    ->schema([
                        ViewField::make('map')
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
                    ]),

                KeyValue::make('meta')
                    ->keyLabel('Key')
                    ->valueLabel('Value')
                    ->columnSpanFull()
                    ->helperText('Raw metadata for this dentist'),
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
                        $path = ltrim((string) $path, '/');
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
                    ->defaultImageUrl(null)
                    ->sortable()
                    ->toggleable(),
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
                    ->sortable()
                    ->toggleable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('specialty')
                    ->label('Specialty')
                    ->getStateUsing(fn (Provider $record): string => $record->meta['specialty'] ?? '')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('meta->specialty', 'like', "%{$search}%");
                    })
                    ->sortable()
                    ->toggleable()
                    ->limit(20),
                Tables\Columns\IconColumn::make('has_image')
                    ->label('Image')
                    ->boolean()
                    ->getStateUsing(function (Provider $record): bool {
                        $path = $record->meta['enhanced_photo_path'] ?? null;
                        if (! $path) {
                            return false;
                        }

                        $path = ltrim((string) $path, '/');
                        if (! str_starts_with($path, 'storage/')) {
                            $path = 'storage/'.ltrim($path, 'storage/');
                        }

                        return file_exists(public_path($path));
                    })
                    ->trueIcon('heroicon-o-photo')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('has_enhanced_profile')
                    ->label('Enhanced Profile')
                    ->boolean()
                    ->getStateUsing(function (Provider $record): bool {
                        $enhancedProfile = $record->meta['enhanced-profile-text'] ?? '';

                        return ! empty(trim($enhancedProfile));
                    })
                    ->trueIcon('heroicon-o-document-text')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('has_address')
                    ->label('Address')
                    ->boolean()
                    ->getStateUsing(function (Provider $record): bool {
                        $street = $record->meta['address-street'] ?? '';
                        $city = $record->meta['address-city'] ?? '';
                        $state = $record->meta['address-state'] ?? '';
                        $zip = $record->meta['address-zip'] ?? '';

                        return ! empty(trim($street)) && ! empty(trim($city)) && ! empty(trim($state));
                    })
                    ->trueIcon('heroicon-o-map-pin')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Location')
                    ->getStateUsing(function (Provider $record): string {
                        $street = $record->meta['address-street'] ?? '';
                        $city = $record->meta['address-city'] ?? '';
                        $state = $record->meta['address-state'] ?? '';
                        $zip = $record->meta['address-zip'] ?? '';

                        if (empty(trim($street)) || empty(trim($city)) || empty(trim($state))) {
                            return 'No address';
                        }

                        $address = trim($street).', '.trim($city).', '.trim($state);
                        if (! empty($zip)) {
                            $address .= ' '.trim($zip);
                        }

                        return $address;
                    })
                    ->limit(40)
                    ->tooltip(function (Provider $record): string {
                        $street = $record->meta['address-street'] ?? '';
                        $city = $record->meta['address-city'] ?? '';
                        $state = $record->meta['address-state'] ?? '';
                        $zip = $record->meta['address-zip'] ?? '';

                        if (empty(trim($street)) || empty(trim($city)) || empty(trim($state))) {
                            return 'No address available';
                        }

                        $address = trim($street).', '.trim($city).', '.trim($state);
                        if (! empty($zip)) {
                            $address .= ' '.trim($zip);
                        }

                        return $address;
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->getStateUsing(fn (Provider $record): string => $record->meta['phone'] ?? '')
                    ->toggleable()
                    ->limit(15),
                Tables\Columns\TextColumn::make('website')
                    ->label('Website')
                    ->getStateUsing(fn (Provider $record): string => $record->meta['website'] ?? '')
                    ->toggleable()
                    ->limit(25)
                    ->url(fn (Provider $record): ?string => $record->meta['website'] ?? null),
                Tables\Columns\IconColumn::make('best_of_washingtonian')
                    ->label('Best of WA')
                    ->boolean()
                    ->getStateUsing(fn (Provider $record): bool => (bool) ($record->meta['best_of_washingtonian'] ?? false))
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->toggleable(),
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

                Tables\Filters\TernaryFilter::make('has_image')
                    ->label('Has Image')
                    ->placeholder('All dentists')
                    ->trueLabel('With images')
                    ->falseLabel('Without images')
                    ->queries(
                        true: fn (Builder $query) => $query->where(function ($query) {
                            $query->whereNotNull('meta->enhanced_photo_path')
                                ->where('meta->enhanced_photo_path', '!=', '');
                        }),
                        false: fn (Builder $query) => $query->where(function ($query) {
                            $query->whereNull('meta->enhanced_photo_path')
                                ->orWhere('meta->enhanced_photo_path', '=', '');
                        }),
                    ),

                Tables\Filters\TernaryFilter::make('has_address')
                    ->label('Has Address')
                    ->placeholder('All dentists')
                    ->trueLabel('With addresses')
                    ->falseLabel('Without addresses')
                    ->queries(
                        true: fn (Builder $query) => $query->where(function ($query) {
                            $query->whereNotNull('meta->address-street')
                                ->where('meta->address-street', '!=', '')
                                ->whereNotNull('meta->address-city')
                                ->where('meta->address-city', '!=', '')
                                ->whereNotNull('meta->address-state')
                                ->where('meta->address-state', '!=', '');
                        }),
                        false: fn (Builder $query) => $query->where(function ($query) {
                            $query->whereNull('meta->address-street')
                                ->orWhere('meta->address-street', '=', '')
                                ->orWhereNull('meta->address-city')
                                ->orWhere('meta->address-city', '=', '')
                                ->orWhereNull('meta->address-state')
                                ->orWhere('meta->address-state', '=', '');
                        }),
                    ),

                Tables\Filters\TernaryFilter::make('has_enhanced_profile')
                    ->label('Has Enhanced Profile')
                    ->placeholder('All dentists')
                    ->trueLabel('With enhanced profile')
                    ->falseLabel('Without enhanced profile')
                    ->queries(
                        true: fn (Builder $query) => $query->where(function ($query) {
                            $query->whereNotNull('meta->enhanced-profile-text')
                                ->where('meta->enhanced-profile-text', '!=', '');
                        }),
                        false: fn (Builder $query) => $query->where(function ($query) {
                            $query->whereNull('meta->enhanced-profile-text')
                                ->orWhere('meta->enhanced-profile-text', '=', '');
                        }),
                    ),

                Tables\Filters\TernaryFilter::make('has_awards')
                    ->label('Has Awards')
                    ->placeholder('All dentists')
                    ->trueLabel('With awards')
                    ->falseLabel('Without awards')
                    ->queries(
                        true: fn (Builder $query) => $query->where('meta->best_of_washingtonian', true),
                        false: fn (Builder $query) => $query->where(function ($query) {
                            $query->where('meta->best_of_washingtonian', false)
                                ->orWhereNull('meta->best_of_washingtonian');
                        }),
                    ),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
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
