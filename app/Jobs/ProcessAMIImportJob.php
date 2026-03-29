<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Job\AMIImportService;

class ProcessAMIImportJob implements ShouldQueue
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
        app(AMIImportService::class)
            ->process($this->filePath, $this->historyId);
    }
}
