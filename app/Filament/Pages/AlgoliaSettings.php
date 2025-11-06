<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;
use UnitEnum;

class AlgoliaSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-magnifying-glass';

    protected string $view = 'filament.pages.algolia-settings';

    protected static ?string $navigationLabel = 'Algolia Settings';

    protected static ?string $title = 'Algolia Configuration';

    protected static UnitEnum|string|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'scout_driver' => config('scout.driver', 'algolia'),
            'scout_prefix' => config('scout.prefix', ''),
            'scout_queue' => config('scout.queue', false),
            'scout_identify' => config('scout.identify', false),
            'algolia_app_id' => config('scout.algolia.id', ''),
            'algolia_secret' => config('scout.algolia.secret', ''),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Scout Configuration')
                    ->description('Configure Laravel Scout search engine settings')
                    ->schema([
                        Select::make('scout_driver')
                            ->label('Search Driver')
                            ->options([
                                'algolia' => 'Algolia',
                                'meilisearch' => 'Meilisearch',
                                'typesense' => 'Typesense',
                                'database' => 'Database',
                                'collection' => 'Collection',
                                'null' => 'Null (Disabled)',
                            ])
                            ->required()
                            ->helperText('The search engine driver to use'),
                        TextInput::make('scout_prefix')
                            ->label('Index Prefix')
                            ->helperText('Prefix for all search index names (useful for multi-tenant applications)')
                            ->maxLength(255),
                        Toggle::make('scout_queue')
                            ->label('Queue Data Syncing')
                            ->helperText('Queue search index operations for better performance')
                            ->default(false),
                        Toggle::make('scout_identify')
                            ->label('Identify User')
                            ->helperText('Notify Algolia of the user performing searches (for analytics)')
                            ->default(false),
                    ])
                    ->columns(2),

                Section::make('Algolia Credentials')
                    ->description('Configure your Algolia application credentials')
                    ->schema([
                        TextInput::make('algolia_app_id')
                            ->label('Application ID')
                            ->helperText('Your Algolia Application ID')
                            ->maxLength(255)
                            ->placeholder('Enter your Algolia App ID'),
                        TextInput::make('algolia_secret')
                            ->label('Admin API Key')
                            ->helperText('Your Algolia Admin API Key (keep this secret!)')
                            ->password()
                            ->maxLength(255)
                            ->placeholder('Enter your Algolia Admin API Key'),
                    ])
                    ->columns(2),

                Section::make('Current Configuration')
                    ->description('These values are read from your environment variables')
                    ->schema([
                        TextInput::make('current_app_id')
                            ->label('Current Application ID')
                            ->default(fn () => config('scout.algolia.id') ?: 'Not set')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('current_secret')
                            ->label('Current Secret')
                            ->default(fn () => config('scout.algolia.secret') ? '••••••••' : 'Not set')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Note: This page is for viewing and reference only
        // Actual configuration should be done via .env file
        $instructions = "Add or update these values in your .env file:\n\n";
        $instructions .= "SCOUT_DRIVER={$data['scout_driver']}\n";
        $instructions .= "SCOUT_PREFIX={$data['scout_prefix']}\n";
        $instructions .= "SCOUT_QUEUE=" . ($data['scout_queue'] ? 'true' : 'false') . "\n";
        $instructions .= "SCOUT_IDENTIFY=" . ($data['scout_identify'] ? 'true' : 'false') . "\n";
        $instructions .= "ALGOLIA_APP_ID={$data['algolia_app_id']}\n";
        $instructions .= "ALGOLIA_SECRET={$data['algolia_secret']}\n\n";
        $instructions .= "After updating, run: php artisan config:clear";

        Notification::make()
            ->title('Environment Variables')
            ->body($instructions)
            ->success()
            ->persistent()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('View Configuration Instructions')
                ->submit('save'),
        ];
    }
}

