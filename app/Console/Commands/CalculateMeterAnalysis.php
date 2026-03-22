<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAmiAnalysis;
use App\Jobs\ProcessPrepaidAnalysis;
use App\Models\MeterReading;
use App\Models\PrepaidToken;
use Illuminate\Console\Command;

class CalculateMeterAnalysis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:calculate-meter-analysis';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    public function handle()
    {
        // ======================
        // AMI DATES
        // ======================
        $amiDates = MeterReading::selectRaw('DISTINCT DATE(reading_time) as analysis_date')
            // ->whereBetween('reading_time', ['2026-02-25', '2026-02-26'])
            ->orderBy('analysis_date')
            ->pluck('analysis_date');

        foreach ($amiDates as $date) {
            ProcessAmiAnalysis::dispatch($date);
            $this->info("AMI Job dispatched for {$date}");
        }

        // ======================
        // PREPAID DATES
        // ======================
        $prepaidDates = PrepaidToken::selectRaw('DISTINCT DATE(purchase_date) as analysis_date')
            // ->whereBetween('purchase_date', ['2026-02-25', '2026-02-26'])
            ->orderBy('analysis_date')
            ->pluck('analysis_date');

        foreach ($prepaidDates as $date) {
            ProcessPrepaidAnalysis::dispatch($date);
            $this->info("Prepaid Job dispatched for {$date}");
        }

        $this->info("All jobs dispatched!");
    }
}
