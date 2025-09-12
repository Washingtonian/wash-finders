<?php

// Script to add create functionality to all provider create pages
$providers = [
    'LawyerProvider' => 'lawyers',
    'WeddingVendorProvider' => 'wedding_vendors',
    'RealtorProvider' => 'realtors',
    'FinancialAdvisorProvider' => 'financal_advisors',
    'MortgageProfessionalProvider' => 'mortgage_professionals',
    'IndustryLeaderProvider' => 'industry_leaders',
    'PrivateSchoolProvider' => 'private_schools',
    'RetirementCommunityProvider' => 'retirement_communities',
    'PetVendorProvider' => 'pet_vendors',
    'RentalPropertyProvider' => 'rental_properties',
];

$template = '<?php

namespace App\Filament\Resources\{RESOURCE_NAME}Resource\Pages;

use App\Filament\Resources\{RESOURCE_NAME}Resource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class Create{RESOURCE_NAME} extends CreateRecord
{
    protected static string $resource = {RESOURCE_NAME}Resource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data[\'type\'] = \'{TYPE}\';

        if (empty($data[\'slug\'])) {
            $data[\'slug\'] = Str::slug(\'{TYPE_SLUG}-\' . now()->timestamp);
        }

        return $data;
    }
}';

foreach ($providers as $resourceName => $type) {
    $filePath = "app/Filament/Resources/{$resourceName}Resource/Pages/Create{$resourceName}.php";

    if (file_exists($filePath)) {
        echo "Adding create functionality to {$resourceName}...\n";

        $typeSlug = str_replace('_', '-', $type);

        $content = $template;
        $content = str_replace('{RESOURCE_NAME}', $resourceName, $content);
        $content = str_replace('{TYPE}', $type, $content);
        $content = str_replace('{TYPE_SLUG}', $typeSlug, $content);

        file_put_contents($filePath, $content);
        echo "✓ {$resourceName} create functionality added\n";
    } else {
        echo "✗ {$resourceName} create page not found\n";
    }
}

echo "\nAll create functionality added!\n";
