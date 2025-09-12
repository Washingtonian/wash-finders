<?php

// Script to configure all provider resources
$providers = [
    'LawyerProvider' => ['type' => 'lawyers', 'icon' => 'heroicon-o-scale', 'color' => 'gray'],
    'WeddingVendorProvider' => ['type' => 'wedding_vendors', 'icon' => 'heroicon-o-heart', 'color' => 'danger'],
    'RealtorProvider' => ['type' => 'realtors', 'icon' => 'heroicon-o-building-office-2', 'color' => 'primary'],
    'FinancialAdvisorProvider' => ['type' => 'financal_advisors', 'icon' => 'heroicon-o-banknotes', 'color' => 'success'],
    'MortgageProfessionalProvider' => ['type' => 'mortgage_professionals', 'icon' => 'heroicon-o-home', 'color' => 'info'],
    'IndustryLeaderProvider' => ['type' => 'industry_leaders', 'icon' => 'heroicon-o-star', 'color' => 'primary'],
    'PrivateSchoolProvider' => ['type' => 'private_schools', 'icon' => 'heroicon-o-academic-cap', 'color' => 'warning'],
    'RetirementCommunityProvider' => ['type' => 'retirement_communities', 'icon' => 'heroicon-o-building-office', 'color' => 'info'],
    'HomeResource' => ['type' => 'home_resources', 'icon' => 'heroicon-o-wrench-screwdriver', 'color' => 'warning'],
    'PetVendorProvider' => ['type' => 'pet_vendors', 'icon' => 'heroicon-o-heart', 'color' => 'success'],
    'RentalPropertyProvider' => ['type' => 'rental_properties', 'icon' => 'heroicon-o-home', 'color' => 'success'],
];

foreach ($providers as $resourceName => $config) {
    $className = $resourceName.'Resource';
    $filePath = "app/Filament/Resources/{$className}.php";

    if (file_exists($filePath)) {
        echo "Configuring {$className}...\n";

        $content = file_get_contents($filePath);

        // Replace the model and basic configuration
        $content = preg_replace('/use App\\\\Models\\\\[^;]+;/', 'use App\\Models\\Provider;', $content);
        $content = preg_replace('/protected static \?string \$model = [^;]+;/', 'protected static ?string $model = Provider::class;', $content);
        $content = preg_replace('/protected static \?string \$navigationIcon = [^;]+;/', "protected static ?string \$navigationIcon = '{$config['icon']}';", $content);

        // Add new properties
        $label = ucwords(str_replace('_', ' ', $config['type']));
        $newProperties = "
    protected static ?string \$navigationLabel = '{$label}';
    protected static ?string \$modelLabel = '{$label}';
    protected static ?string \$pluralModelLabel = '{$label}';
    protected static ?string \$navigationGroup = 'Providers';";

        $content = str_replace('protected static ?string $navigationIcon = \''.$config['icon'].'\';',
            'protected static ?string $navigationIcon = \''.$config['icon'].'\';'.$newProperties,
            $content);

        // Add Str import
        if (strpos($content, 'use Illuminate\\Support\\Str;') === false) {
            $content = str_replace('use Illuminate\\Database\\Eloquent\\SoftDeletingScope;',
                'use Illuminate\\Database\\Eloquent\\SoftDeletingScope;'."\nuse Illuminate\\Support\\Str;",
                $content);
        }

        file_put_contents($filePath, $content);
        echo "✓ {$className} configured\n";
    } else {
        echo "✗ {$className} not found\n";
    }
}

echo "\nAll provider resources configured!\n";
