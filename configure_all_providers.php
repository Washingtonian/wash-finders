<?php

// Script to fully configure all provider resources
$providers = [
    'LawyerProvider' => ['type' => 'lawyers', 'icon' => 'heroicon-o-scale', 'color' => 'gray'],
    'WeddingVendorProvider' => ['type' => 'wedding_vendors', 'icon' => 'heroicon-o-heart', 'color' => 'danger'],
    'RealtorProvider' => ['type' => 'realtors', 'icon' => 'heroicon-o-building-office-2', 'color' => 'primary'],
    'FinancialAdvisorProvider' => ['type' => 'financal_advisors', 'icon' => 'heroicon-o-banknotes', 'color' => 'success'],
    'MortgageProfessionalProvider' => ['type' => 'mortgage_professionals', 'icon' => 'heroicon-o-home', 'color' => 'info'],
    'IndustryLeaderProvider' => ['type' => 'industry_leaders', 'icon' => 'heroicon-o-star', 'color' => 'primary'],
    'PrivateSchoolProvider' => ['type' => 'private_schools', 'icon' => 'heroicon-o-academic-cap', 'color' => 'warning'],
    'RetirementCommunityProvider' => ['type' => 'retirement_communities', 'icon' => 'heroicon-o-building-office', 'color' => 'info'],
    'PetVendorProvider' => ['type' => 'pet_vendors', 'icon' => 'heroicon-o-heart', 'color' => 'success'],
    'RentalPropertyProvider' => ['type' => 'rental_properties', 'icon' => 'heroicon-o-home', 'color' => 'success'],
];

$template = '<?php

namespace App\Filament\Resources;

use App\Filament\Resources\{RESOURCE_NAME}Resource\Pages;
use App\Models\Provider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class {RESOURCE_NAME}Resource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static ?string $navigationIcon = \'{ICON}\';

    protected static ?string $navigationLabel = \'{LABEL}\';

    protected static ?string $modelLabel = \'{LABEL}\';

    protected static ?string $pluralModelLabel = \'{LABEL}\';

    protected static ?string $navigationGroup = \'Providers\';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make(\'provider_id\')
                    ->maxLength(255)
                    ->placeholder(\'External provider ID\'),
                Forms\Components\TextInput::make(\'slug\')
                    ->maxLength(255)
                    ->placeholder(\'URL-friendly slug\')
                    ->required(),
                Forms\Components\KeyValue::make(\'meta\')
                    ->keyLabel(\'Key\')
                    ->valueLabel(\'Value\')
                    ->columnSpanFull()
                    ->helperText(\'Additional metadata for this {LABEL_LOWER}\'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->where(\'type\', \'{TYPE}\');
            })
            ->columns([
                Tables\Columns\TextColumn::make(\'id\')
                    ->label(\'ID\')
                    ->sortable(),
                Tables\Columns\TextColumn::make(\'provider_id\')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make(\'slug\')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make(\'meta_count\')
                    ->label(\'Meta Fields\')
                    ->getStateUsing(fn (Provider $record): int => $record->meta->count())
                    ->badge()
                    ->color(\'gray\'),
                Tables\Columns\TextColumn::make(\'created_at\')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make(\'updated_at\')
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
            ->defaultSort(\'created_at\', \'desc\');
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
            \'index\' => Pages\List{RESOURCE_NAME}s::route(\'/\'),
            \'create\' => Pages\Create{RESOURCE_NAME}::route(\'/create\'),
            \'edit\' => Pages\Edit{RESOURCE_NAME}::route(\'/{record}/edit\'),
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
        return static::getModel()::where(\'type\', \'{TYPE}\')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return \'{COLOR}\';
    }
}';

foreach ($providers as $resourceName => $config) {
    $className = $resourceName.'Resource';
    $filePath = "app/Filament/Resources/{$className}.php";

    if (file_exists($filePath)) {
        echo "Configuring {$className}...\n";

        $label = ucwords(str_replace('_', ' ', $config['type']));
        $labelLower = strtolower($label);

        $content = $template;
        $content = str_replace('{RESOURCE_NAME}', $resourceName, $content);
        $content = str_replace('{ICON}', $config['icon'], $content);
        $content = str_replace('{LABEL}', $label, $content);
        $content = str_replace('{LABEL_LOWER}', $labelLower, $content);
        $content = str_replace('{TYPE}', $config['type'], $content);
        $content = str_replace('{COLOR}', $config['color'], $content);

        file_put_contents($filePath, $content);
        echo "✓ {$className} configured\n";
    } else {
        echo "✗ {$className} not found\n";
    }
}

echo "\nAll provider resources fully configured!\n";
