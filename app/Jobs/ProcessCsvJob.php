<?php

namespace App\Jobs;

use App\Pipelines\DownloadCsv;
use App\Pipelines\ProcessCsv;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $type;

    public $remoteUrl;

    public $stats;

    protected const CACHE_DURATION = 0; // 120 * 24 * 60; // 120 days in minutes

    public $fileName = 'imported_csv.csv';

    public function __construct($type, $remoteUrl)
    {
        $this->type = $type;
        $this->remoteUrl = $remoteUrl;
    }

    public function handle()
    {
        app(Pipeline::class)
            ->send($this)
            ->through([
                DownloadCsv::class,
                ProcessCsv::class,
            ])
            ->then(fn () => Storage::delete($this->fileName));
    }
}
