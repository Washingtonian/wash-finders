<?php

namespace App\Filament\Widgets;

use App\Models\Import;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ImportStatusOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $runningCount = Import::where('last_run_status', 'running')->count();
        $completedCount = Import::where('last_run_status', 'completed')->count();
        $pendingCount = Import::where('last_run_status', 'pending')->count();
        $failedCount = Import::where('last_run_status', 'failed')->count();
        $totalCount = Import::count();

        $stats = [];

        // Running imports
        $stats[] = Stat::make('Running Imports', $runningCount)
            ->description($runningCount > 0 ? 'Currently processing' : 'No imports running')
            ->descriptionIcon($runningCount > 0 ? 'heroicon-m-arrow-path' : 'heroicon-m-check-circle')
            ->color($runningCount > 0 ? 'warning' : 'success')
            ->icon('heroicon-o-arrow-path');

        // Completed imports
        $stats[] = Stat::make('Completed Imports', $completedCount)
            ->description($completedCount > 0 ? 'Successfully finished' : 'No completed imports')
            ->descriptionIcon($completedCount > 0 ? 'heroicon-m-check-circle' : 'heroicon-m-clock')
            ->color($completedCount > 0 ? 'success' : 'gray')
            ->icon('heroicon-o-check-circle');

        // Failed imports
        $stats[] = Stat::make('Failed Imports', $failedCount)
            ->description($failedCount > 0 ? 'Need attention' : 'No failed imports')
            ->descriptionIcon($failedCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
            ->color($failedCount > 0 ? 'danger' : 'success')
            ->icon('heroicon-o-x-circle');

        // Total imports
        $stats[] = Stat::make('Total Imports', $totalCount)
            ->description('All import configurations')
            ->descriptionIcon('heroicon-m-document-text')
            ->color('primary')
            ->icon('heroicon-o-document-arrow-down');

        return $stats;
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
