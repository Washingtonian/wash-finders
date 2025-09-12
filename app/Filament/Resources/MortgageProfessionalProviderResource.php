<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MortgageProfessionalProviderResource\Pages;
use App\Models\Provider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MortgageProfessionalProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Mortgage Professionals';

    protected static ?string $modelLabel = 'Mortgage Professional';

    protected static ?string $pluralModelLabel = 'Mortgage Professionals';

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
                    ->directory('enhanced_photos/mortgage_professionals')
                    ->visibility('public'),
                Forms\Components\KeyValue::make('meta')
                    ->keyLabel('Key')
                    ->valueLabel('Value')
                    ->columnSpanFull()
                    ->addActionLabel('Add Field'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'mortgage_professionals'))
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Photo')
                    ->getStateUsing(fn (Provider $record): ?string => $record->meta['enhanced_photo_path'] ?? null)
                    ->size(60)
                    ->circular()
                    ->defaultImageUrl('/images/placeholder-avatar.png'),
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
            'index' => Pages\ListMortgageProfessionalProviders::route('/'),
            'create' => Pages\CreateMortgageProfessionalProvider::route('/create'),
            'edit' => Pages\EditMortgageProfessionalProvider::route('/{record}/edit'),
        ];
    }

    public static function getUrl(string $name = 'index', array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        $parameters['type'] = 'mortgage_professionals';

        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant);
    }
}
