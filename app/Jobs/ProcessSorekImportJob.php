<?php

namespace App\Jobs;

use App\Services\Job\SorekImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSorekImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $historyId;

    public function __construct($filePath, $historyId)
    {
        $this->filePath = $filePath;
        $this->historyId = $historyId;
    }

    public function handle()
    {
        app(SorekImportService::class)
            ->process($this->filePath, $this->historyId);
    }
}
