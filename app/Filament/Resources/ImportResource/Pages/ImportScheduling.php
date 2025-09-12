<?php

namespace App\Filament\Resources\ImportResource\Pages;

use App\Filament\Resources\ImportResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;

class ImportScheduling extends ManageRelatedRecords
{
    protected static string $resource = ImportResource::class;

    protected static string $relationship = 'importHistories';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Schedule Configuration')
                    ->description('Configure when and how often this import should run automatically')
                    ->schema([
                        Forms\Components\Toggle::make('schedule_enabled')
                            ->label('Enable Scheduling')
                            ->helperText('Turn on automatic scheduling for this import')
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('schedule_frequency')
                                    ->label('Schedule Frequency')
                                    ->options([
                                        'daily' => 'Daily',
                                        'weekly' => 'Weekly',
                                        'monthly' => 'Monthly',
                                    ])
                                    ->visible(fn (Forms\Get $get): bool => $get('schedule_enabled'))
                                    ->required(fn (Forms\Get $get): bool => $get('schedule_enabled'))
                                    ->default('weekly')
                                    ->live(),

                                Forms\Components\TimePicker::make('schedule_time')
                                    ->label('Run Time')
                                    ->visible(fn (Forms\Get $get): bool => $get('schedule_enabled'))
                                    ->required(fn (Forms\Get $get): bool => $get('schedule_enabled'))
                                    ->default('08:00')
                                    ->seconds(false)
                                    ->format('H:i'),
                            ]),

                        Forms\Components\CheckboxList::make('schedule_days')
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
                            ->visible(fn (Forms\Get $get): bool => $get('schedule_enabled') && $get('schedule_frequency') === 'weekly')
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
            Actions\EditAction::make()
                ->label('Edit Schedule'),
        ];
    }
}
