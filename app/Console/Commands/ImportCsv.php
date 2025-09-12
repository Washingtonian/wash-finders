<?php

namespace App\Console\Commands;

use App\Jobs\ProcessCsvJob;
use Illuminate\Console\Command;

class ImportCsv extends Command
{
    protected $signature = 'app:import-csv';

    protected $description = 'Import CSV files from configured URLs for specific provider types';

    public function handle()
    {
        $csvUrls = config('providers');

        foreach ($csvUrls as $type => $url) {
            $this->info("Dispatching job to import {$type} providers from CSV...");
            ProcessCsvJob::dispatch($type, $url);
        }

        $this->info('All CSV import jobs have been dispatched.');

        return 0;
    }
}
