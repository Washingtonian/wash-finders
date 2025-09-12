<?php

// Script to fix search columns in all provider resources
$resources = [
    'DoctorProviderResource',
    'LawyerProviderResource',
    'WeddingVendorProviderResource',
    'RealtorProviderResource',
    'FinancialAdvisorProviderResource',
    'MortgageProfessionalProviderResource',
    'IndustryLeaderProviderResource',
    'PrivateSchoolProviderResource',
    'RetirementCommunityProviderResource',
    'HomeResourceProviderResource',
    'PetVendorProviderResource',
    'RentalPropertyProviderResource',
];

foreach ($resources as $resourceName) {
    $filePath = "app/Filament/Resources/{$resourceName}.php";

    if (file_exists($filePath)) {
        echo "Fixing {$resourceName}...\n";

        $content = file_get_contents($filePath);

        // Fix provider_id search
        $content = preg_replace(
            '/Tables\\Columns\\TextColumn::make\(\'provider_id\'\)\s*->searchable\(\)\s*->sortable\(\),/',
            'Tables\Columns\TextColumn::make(\'provider_id\')
                    ->getStateUsing(fn (Provider $record): string => $record->meta[\'id\'] ?? $record->provider_id ?? \'\')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(\'meta->id\', \'like\', "%{$search}%")
                                   ->orWhere(\'provider_id\', \'like\', "%{$search}%");
                    })
                    ->sortable(),',
            $content
        );

        // Fix title search
        $content = preg_replace(
            '/Tables\\Columns\\TextColumn::make\(\'title\'\)\s*->label\(\'Name\'\)\s*->getStateUsing\([^}]+\)\s*->searchable\(\)\s*->sortable\(\)\s*->limit\(30\),/',
            'Tables\Columns\TextColumn::make(\'title\')
                    ->label(\'Name\')
                    ->getStateUsing(fn (Provider $record): string => $record->meta[\'title\'] ?? \'Untitled\')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(\'meta->title\', \'like\', "%{$search}%");
                    })
                    ->sortable()
                    ->limit(30),',
            $content
        );

        // Fix business_name search
        $content = preg_replace(
            '/Tables\\Columns\\TextColumn::make\(\'business_name\'\)\s*->label\(\'Business\'\)\s*->getStateUsing\([^}]+\)\s*->searchable\(\)\s*->limit\(30\),/',
            'Tables\Columns\TextColumn::make(\'business_name\')
                    ->label(\'Business\')
                    ->getStateUsing(fn (Provider $record): string => $record->meta[\'business_name\'] ?? \'\')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(\'meta->business_name\', \'like\', "%{$search}%");
                    })
                    ->limit(30),',
            $content
        );

        // Fix specialty search
        $content = preg_replace(
            '/Tables\\Columns\\TextColumn::make\(\'specialty\'\)\s*->label\(\'Specialty\'\)\s*->getStateUsing\([^}]+\)\s*->searchable\(\)\s*->limit\(20\),/',
            'Tables\Columns\TextColumn::make(\'specialty\')
                    ->label(\'Specialty\')
                    ->getStateUsing(fn (Provider $record): string => $record->meta[\'specialty\'] ?? \'\')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(\'meta->specialty\', \'like\', "%{$search}%");
                    })
                    ->limit(20),',
            $content
        );

        file_put_contents($filePath, $content);
        echo "✓ {$resourceName} fixed\n";
    } else {
        echo "✗ {$resourceName} not found\n";
    }
}

echo "\nAll provider resources search columns fixed!\n";
