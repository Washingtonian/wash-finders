<?php

namespace App\Filament\Pages;

use App\Models\Import;
use App\Models\Provider;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class DataManagement extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected string $view = 'filament.pages.data-management';

    protected static ?string $navigationLabel = 'Data Management';

    protected static ?string $title = 'Export & Import Data';

    protected static UnitEnum|string|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 11;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'export_format' => 'json',
            'include_imports' => true,
            'include_deleted' => false,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Export Data')
                    ->description('Export all providers and import configurations from the database')
                    ->schema([
                        Select::make('export_format')
                            ->label('Export Format')
                            ->options([
                                'json' => 'JSON (Recommended)',
                                'csv' => 'CSV',
                                'sql' => 'SQL',
                            ])
                            ->required()
                            ->default('json'),
                        Toggle::make('include_imports')
                            ->label('Include Import Configurations')
                            ->helperText('Export import settings and configurations')
                            ->default(true),
                        Toggle::make('include_deleted')
                            ->label('Include Soft Deleted Records')
                            ->helperText('Include records that have been soft deleted')
                            ->default(false),
                    ])
                    ->columns(2),

                Section::make('Import Data')
                    ->description('Import previously exported data back into the database')
                    ->schema([
                        FileUpload::make('import_file')
                            ->label('Import File')
                            ->acceptedFileTypes(['application/json', 'text/csv', 'text/plain', 'application/sql', 'text/x-sql'])
                            ->helperText('Upload a JSON, CSV, or SQL file exported from this system')
                            ->disk('local')
                            ->directory('imports')
                            ->visibility('private')
                            ->maxSize(10240), // 10MB
                        Toggle::make('overwrite_existing')
                            ->label('Overwrite Existing Records')
                            ->helperText('If enabled, existing records with the same ID will be updated. Otherwise, they will be skipped.')
                            ->default(false),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function exportData(): void
    {
        $data = $this->form->getState();
        $format = $data['export_format'] ?? 'json';
        $includeImports = $data['include_imports'] ?? true;
        $includeDeleted = $data['include_deleted'] ?? false;

        try {
            $exportData = [];

            // Export Providers
            $providersQuery = Provider::query();
            if (! $includeDeleted) {
                $providersQuery->whereNull('deleted_at');
            } else {
                $providersQuery->withTrashed();
            }

            $providers = $providersQuery->get()->map(function ($provider) {
                return [
                    'id' => $provider->id,
                    'type' => $provider->type,
                    'provider_id' => $provider->provider_id,
                    'slug' => $provider->slug,
                    'meta' => $provider->meta->toArray(),
                    'created_at' => $provider->created_at?->toIso8601String(),
                    'updated_at' => $provider->updated_at?->toIso8601String(),
                    'deleted_at' => $provider->deleted_at?->toIso8601String(),
                ];
            });

            $exportData['providers'] = $providers->toArray();
            $exportData['export_metadata'] = [
                'exported_at' => now()->toIso8601String(),
                'total_providers' => $providers->count(),
                'format' => $format,
            ];

            // Export Imports if requested
            if ($includeImports) {
                $importsQuery = Import::query();
                if (! $includeDeleted) {
                    $importsQuery->whereNull('deleted_at');
                } else {
                    $importsQuery->withTrashed();
                }

                $imports = $importsQuery->get()->map(function ($import) {
                    return [
                        'id' => $import->id,
                        'name' => $import->name,
                        'provider_type' => $import->provider_type,
                        'description' => $import->description,
                        'csv_url' => $import->csv_url,
                        'version' => $import->version,
                        'is_active' => $import->is_active,
                        'is_current_version' => $import->is_current_version,
                        'import_settings' => $import->import_settings,
                        'mapping_config' => $import->mapping_config,
                        'schedule_enabled' => $import->schedule_enabled,
                        'schedule_frequency' => $import->schedule_frequency,
                        'schedule_time' => $import->schedule_time?->format('H:i:s'),
                        'schedule_days' => $import->schedule_days,
                        'created_at' => $import->created_at?->toIso8601String(),
                        'updated_at' => $import->updated_at?->toIso8601String(),
                        'deleted_at' => $import->deleted_at?->toIso8601String(),
                    ];
                });

                $exportData['imports'] = $imports->toArray();
                $exportData['export_metadata']['total_imports'] = $imports->count();
            }

            // Generate filename
            $filename = 'wash-finders-export-'.now()->format('Y-m-d-His').'.'.$format;
            $filepath = storage_path('app/exports/'.$filename);

            // Ensure directory exists
            if (! file_exists(storage_path('app/exports'))) {
                mkdir(storage_path('app/exports'), 0755, true);
            }

            if ($format === 'json') {
                file_put_contents($filepath, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            } elseif ($format === 'sql') {
                // SQL export - generate INSERT statements
                $sql = "-- ============================================\n";
                $sql .= "-- Wash Finders Data Export\n";
                $sql .= "-- ============================================\n";
                $sql .= "-- Exported at: {$exportData['export_metadata']['exported_at']}\n";
                $sql .= "-- Total Providers: {$exportData['export_metadata']['total_providers']}\n";
                if (isset($exportData['export_metadata']['total_imports'])) {
                    $sql .= "-- Total Imports: {$exportData['export_metadata']['total_imports']}\n";
                }
                $sql .= "--\n";
                $sql .= "-- WARNING: This SQL file will TRUNCATE existing tables before inserting data.\n";
                $sql .= "-- Make sure to backup your database before running this script.\n";
                $sql .= "-- ============================================\n\n";

                $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
                $sql .= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n";

                // Export Providers
                if (!empty($exportData['providers'])) {
                    $sql .= "-- ============================================\n";
                    $sql .= "-- Providers Table\n";
                    $sql .= "-- ============================================\n";
                    $sql .= "TRUNCATE TABLE `providers`;\n\n";

                    foreach ($exportData['providers'] as $provider) {
                        $sql .= $this->generateProviderInsert($provider);
                    }
                    $sql .= "\n";
                }

                // Export Imports
                if ($includeImports && !empty($exportData['imports'])) {
                    $sql .= "-- ============================================\n";
                    $sql .= "-- Imports Table\n";
                    $sql .= "-- ============================================\n";
                    $sql .= "TRUNCATE TABLE `imports`;\n\n";

                    foreach ($exportData['imports'] as $import) {
                        $sql .= $this->generateImportInsert($import);
                    }
                    $sql .= "\n";
                }

                $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
                $sql .= "-- Export completed successfully\n";
                file_put_contents($filepath, $sql);
            } else {
                // CSV export - flatten providers
                $csvData = [];
                $csvData[] = ['id', 'type', 'provider_id', 'slug', 'meta_json', 'created_at', 'updated_at', 'deleted_at'];

                foreach ($exportData['providers'] as $provider) {
                    $csvData[] = [
                        $provider['id'],
                        $provider['type'],
                        $provider['provider_id'],
                        $provider['slug'],
                        json_encode($provider['meta']),
                        $provider['created_at'],
                        $provider['updated_at'],
                        $provider['deleted_at'],
                    ];
                }

                $fp = fopen($filepath, 'w');
                foreach ($csvData as $row) {
                    fputcsv($fp, $row);
                }
                fclose($fp);
            }

            $downloadUrl = route('admin.data-management.download', ['file' => $filename]);
            
            Notification::make()
                ->title('Export Successful')
                ->body("Data exported successfully. {$providers->count()} providers exported. <a href=\"{$downloadUrl}\" target=\"_blank\" class=\"underline font-semibold\">Download File</a>")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Export Failed')
                ->body('An error occurred while exporting data: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function importData(): void
    {
        $data = $this->form->getState();
        $filePath = $data['import_file'] ?? null;
        $overwrite = $data['overwrite_existing'] ?? false;

        if (! $filePath) {
            Notification::make()
                ->title('No File Selected')
                ->body('Please select a file to import.')
                ->warning()
                ->send();

            return;
        }

        try {
            $fullPath = Storage::path($filePath);

            if (! file_exists($fullPath)) {
                throw new \Exception('File not found');
            }

            $fileContent = file_get_contents($fullPath);
            $importData = json_decode($fileContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON file: '.json_last_error_msg());
            }

            DB::beginTransaction();

            $importedProviders = 0;
            $updatedProviders = 0;
            $skippedProviders = 0;
            $importedImports = 0;

            // Import Providers
            if (isset($importData['providers'])) {
                foreach ($importData['providers'] as $providerData) {
                    $existing = Provider::where('id', $providerData['id'])->first();

                    if ($existing) {
                        if ($overwrite) {
                            $existing->update([
                                'type' => $providerData['type'],
                                'provider_id' => $providerData['provider_id'],
                                'slug' => $providerData['slug'],
                                'meta' => $providerData['meta'],
                            ]);
                            $updatedProviders++;
                        } else {
                            $skippedProviders++;

                            continue;
                        }
                    } else {
                        Provider::create([
                            'id' => $providerData['id'],
                            'type' => $providerData['type'],
                            'provider_id' => $providerData['provider_id'],
                            'slug' => $providerData['slug'],
                            'meta' => $providerData['meta'],
                        ]);
                        $importedProviders++;
                    }
                }
            }

            // Import Imports if present
            if (isset($importData['imports'])) {
                foreach ($importData['imports'] as $importRecord) {
                    $existing = Import::where('id', $importRecord['id'])->first();

                    if ($existing) {
                        if ($overwrite) {
                            $existing->update($importRecord);
                            $importedImports++;
                        }
                    } else {
                        Import::create($importRecord);
                        $importedImports++;
                    }
                }
            }

            DB::commit();

            Notification::make()
                ->title('Import Successful')
                ->body("Imported {$importedProviders} providers, updated {$updatedProviders}, skipped {$skippedProviders}. Imported {$importedImports} import configurations.")
                ->success()
                ->send();

            // Clean up uploaded file
            Storage::delete($filePath);

        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Import Failed')
                ->body('An error occurred while importing data: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action('exportData')
                ->requiresConfirmation()
                ->modalHeading('Export All Data')
                ->modalDescription('This will export all providers and import configurations from the database. This may take a moment for large datasets.')
                ->modalSubmitActionLabel('Export'),
            Action::make('import')
                ->label('Import Data')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->action('importData')
                ->requiresConfirmation()
                ->modalHeading('Import Data')
                ->modalDescription('This will import data from the selected file. Existing records may be updated if "Overwrite Existing Records" is enabled.')
                ->modalSubmitActionLabel('Import'),
        ];
    }

    /**
     * Generate SQL INSERT statement for a provider
     */
    private function generateProviderInsert(array $provider): string
    {
        $id = $this->escapeSqlValue($provider['id']);
        $type = $this->escapeSqlValue($provider['type']);
        $providerId = $provider['provider_id'] ? $this->escapeSqlValue($provider['provider_id']) : 'NULL';
        $slug = $provider['slug'] ? $this->escapeSqlValue($provider['slug']) : 'NULL';
        $meta = $this->escapeSqlValue(json_encode($provider['meta']));
        $createdAt = $provider['created_at'] ? $this->escapeSqlValue($provider['created_at']) : 'NULL';
        $updatedAt = $provider['updated_at'] ? $this->escapeSqlValue($provider['updated_at']) : 'NULL';
        $deletedAt = $provider['deleted_at'] ? $this->escapeSqlValue($provider['deleted_at']) : 'NULL';

        return "INSERT INTO `providers` (`id`, `type`, `provider_id`, `slug`, `meta`, `created_at`, `updated_at`, `deleted_at`) VALUES ({$id}, {$type}, {$providerId}, {$slug}, {$meta}, {$createdAt}, {$updatedAt}, {$deletedAt});\n";
    }

    /**
     * Generate SQL INSERT statement for an import
     */
    private function generateImportInsert(array $import): string
    {
        $id = $this->escapeSqlValue($import['id']);
        $name = $this->escapeSqlValue($import['name']);
        $providerType = $this->escapeSqlValue($import['provider_type']);
        $description = $import['description'] ? $this->escapeSqlValue($import['description']) : 'NULL';
        $csvUrl = $this->escapeSqlValue($import['csv_url']);
        $version = $this->escapeSqlValue($import['version'] ?? '1.0');
        $isActive = $import['is_active'] ? 1 : 0;
        $isCurrentVersion = $import['is_current_version'] ? 1 : 0;
        $importSettings = $import['import_settings'] ? $this->escapeSqlValue(json_encode($import['import_settings'])) : 'NULL';
        $mappingConfig = $import['mapping_config'] ? $this->escapeSqlValue(json_encode($import['mapping_config'])) : 'NULL';
        $scheduleEnabled = $import['schedule_enabled'] ? 1 : 0;
        $scheduleFrequency = $import['schedule_frequency'] ? $this->escapeSqlValue($import['schedule_frequency']) : 'NULL';
        $scheduleTime = $import['schedule_time'] ? $this->escapeSqlValue($import['schedule_time']) : 'NULL';
        $scheduleDays = $import['schedule_days'] ? $this->escapeSqlValue(json_encode($import['schedule_days'])) : 'NULL';
        $createdAt = $import['created_at'] ? $this->escapeSqlValue($import['created_at']) : 'NULL';
        $updatedAt = $import['updated_at'] ? $this->escapeSqlValue($import['updated_at']) : 'NULL';
        $deletedAt = $import['deleted_at'] ? $this->escapeSqlValue($import['deleted_at']) : 'NULL';

        return "INSERT INTO `imports` (`id`, `name`, `provider_type`, `description`, `csv_url`, `version`, `is_active`, `is_current_version`, `import_settings`, `mapping_config`, `schedule_enabled`, `schedule_frequency`, `schedule_time`, `schedule_days`, `created_at`, `updated_at`, `deleted_at`) VALUES ({$id}, {$name}, {$providerType}, {$description}, {$csvUrl}, {$version}, {$isActive}, {$isCurrentVersion}, {$importSettings}, {$mappingConfig}, {$scheduleEnabled}, {$scheduleFrequency}, {$scheduleTime}, {$scheduleDays}, {$createdAt}, {$updatedAt}, {$deletedAt});\n";
    }

    /**
     * Escape SQL value for safe insertion
     */
    private function escapeSqlValue($value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        // Use Laravel's DB connection to escape
        return DB::getPdo()->quote($value);
    }
}
