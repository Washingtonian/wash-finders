<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ViewImportLogs extends Command
{
    protected $signature = 'import:logs {--follow : Follow the log file in real-time} {--lines=50 : Number of lines to show}';

    protected $description = 'View import process logs with filtering';

    public function handle()
    {
        $logPath = storage_path('logs/laravel.log');

        if (! File::exists($logPath)) {
            $this->error('Log file not found: '.$logPath);

            return 1;
        }

        $lines = $this->option('lines');
        $follow = $this->option('follow');

        if ($follow) {
            $this->info('Following import logs (Ctrl+C to stop)...');
            $this->line('');

            // Use tail -f to follow the log file
            $command = "tail -f -n {$lines} {$logPath} | grep -E '(Import|CSV|Processing|Download)'";
            passthru($command);
        } else {
            $this->info("Showing last {$lines} import-related log entries:");
            $this->line('');

            // Get the last N lines and filter for import-related entries
            $command = "tail -n {$lines} {$logPath} | grep -E '(Import|CSV|Processing|Download)'";
            $output = shell_exec($command);

            if (empty($output)) {
                $this->warn('No import-related log entries found in the last '.$lines.' lines.');
                $this->line('Try running: php artisan import:logs --follow');
            } else {
                $this->line($output);
            }
        }

        return 0;
    }
}
