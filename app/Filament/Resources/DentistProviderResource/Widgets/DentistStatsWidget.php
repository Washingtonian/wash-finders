<?php

namespace App\Filament\Resources\DentistProviderResource\Widgets;

use App\Models\Provider;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DentistStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalDentists = Provider::where('type', 'dentists')->count();

        // Count dentists with images
        $withImages = Provider::where('type', 'dentists')
            ->where(function ($query) {
                $query->whereNotNull('meta->enhanced_photo_path')
                    ->where('meta->enhanced_photo_path', '!=', '');
            })
            ->get()
            ->filter(function ($provider) {
                $path = $provider->meta['enhanced_photo_path'] ?? null;
                if (! $path) {
                    return false;
                }

                $path = ltrim((string) $path, '/');
                if (! str_starts_with($path, 'storage/')) {
                    $path = 'storage/'.ltrim($path, 'storage/');
                }

                return file_exists(public_path($path));
            })
            ->count();

        // Count dentists with addresses
        $withAddresses = Provider::where('type', 'dentists')
            ->where(function ($query) {
                $query->whereNotNull('meta->address-street')
                    ->where('meta->address-street', '!=', '')
                    ->whereNotNull('meta->address-city')
                    ->where('meta->address-city', '!=', '')
                    ->whereNotNull('meta->address-state')
                    ->where('meta->address-state', '!=', '');
            })
            ->count();

        // Count dentists with enhanced profile text
        $withEnhancedProfile = Provider::where('type', 'dentists')
            ->where(function ($query) {
                $query->whereNotNull('meta->enhanced-profile-text')
                    ->where('meta->enhanced-profile-text', '!=', '');
            })
            ->count();

        // Count dentists with awards (Best of Washingtonian)
        $withAwards = Provider::where('type', 'dentists')
            ->where('meta->best_of_washingtonian', true)
            ->count();

        return [
            Stat::make('With Images', $withImages)
                ->description('out of '.$totalDentists.' total dentists')
                ->descriptionIcon('heroicon-m-photo')
                ->color('success'),

            Stat::make('With Addresses', $withAddresses)
                ->description('out of '.$totalDentists.' total dentists')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('info'),

            Stat::make('With Enhanced Profile', $withEnhancedProfile)
                ->description('out of '.$totalDentists.' total dentists')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),

            Stat::make('With Awards', $withAwards)
                ->description('Best of Washingtonian recipients')
                ->descriptionIcon('heroicon-m-star')
                ->color('danger'),
        ];
    }
}
