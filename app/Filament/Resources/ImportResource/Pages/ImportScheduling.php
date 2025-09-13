<?php

namespace App\Filament\Resources\ImportResource\Pages;

use App\Filament\Resources\ImportResource;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ImportScheduling extends ManageRelatedRecords
{
    protected static string $resource = ImportResource::class;

    protected static string $relationship = 'importHistories';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Schedule Configuration')
                    ->description('Configure when and how often this import should run automatically')
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

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit Schedule'),
        ];
    }
}
