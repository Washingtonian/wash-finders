<?php

// Script to add import actions to all provider list pages
$providers = [
    'LawyerProvider' => 'lawyers',
    'WeddingVendorProvider' => 'wedding_vendors',
    'RealtorProvider' => 'realtors',
    'FinancialAdvisorProvider' => 'financal_advisors',
    'MortgageProfessionalProvider' => 'mortgage_professionals',
    'IndustryLeaderProvider' => 'industry_leaders',
    'PrivateSchoolProvider' => 'private_schools',
    'RetirementCommunityProvider' => 'retirement_communities',
    'HomeResourceProvider' => 'home_resources',
    'PetVendorProvider' => 'pet_vendors',
    'RentalPropertyProvider' => 'rental_properties',
];

$template = '<?php

namespace App\Filament\Resources\{RESOURCE_NAME}Resource\Pages;

use App\Filament\Resources\{RESOURCE_NAME}Resource;
use App\Models\Import;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class List{RESOURCE_NAME}s extends ListRecords
{
    protected static string $resource = {RESOURCE_NAME}Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make(\'run_{TYPE}_import\')
                ->label(\'Run {LABEL} Import\')
                ->icon(\'heroicon-o-bolt\')
                ->color(\'primary\')
                ->action(function () {
                    $import = Import::where(\'provider_type\', \'{TYPE}\')->first();

                    if (!$import) {
                        \\Filament\\Notifications\\Notification::make()
                            ->title(\'No {LABEL_LOWER} import found\')
                            ->danger()
                            ->send();
                        return;
                    }

                    \\App\\Jobs\\ProcessImportJob::dispatch($import);

                    $import->update([
                        \'last_run_status\' => \'running\',
                        \'last_run_at\' => now(),
                    ]);

                    \\Filament\\Notifications\\Notification::make()
                        ->title(\'{LABEL} Import Started\')
                        ->body(\'The {LABEL_LOWER} import has been queued and will start processing shortly.\')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading(\'Run {LABEL} Import\')
                ->modalDescription(\'This will start the {LABEL_LOWER} import process.\')
                ->modalSubmitActionLabel(\'Start Import\')
                ->visible(fn () => Import::where(\'provider_type\', \'{TYPE}\')->exists()),
        ];
    }
}';

foreach ($providers as $resourceName => $type) {
    $filePath = "app/Filament/Resources/{$resourceName}Resource/Pages/List{$resourceName}s.php";

    if (file_exists($filePath)) {
        echo "Adding import action to {$resourceName}...\n";

        $label = ucwords(str_replace('_', ' ', $type));
        $labelLower = strtolower($label);

        $content = $template;
        $content = str_replace('{RESOURCE_NAME}', $resourceName, $content);
        $content = str_replace('{TYPE}', $type, $content);
        $content = str_replace('{LABEL}', $label, $content);
        $content = str_replace('{LABEL_LOWER}', $labelLower, $content);

        file_put_contents($filePath, $content);
        echo "✓ {$resourceName} import action added\n";
    } else {
        echo "✗ {$resourceName} list page not found\n";
    }
}

echo "\nAll import actions added!\n";
