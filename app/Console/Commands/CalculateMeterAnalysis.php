<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAmiAnalysis;
use App\Jobs\ProcessMeterAnalysis;
use App\Jobs\ProcessPrepaidAnalysis;
use App\Models\MeterAnalysis;
use App\Models\MeterReading;
use App\Models\Meters;
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
        // $dates = MeterReading::selectRaw('DISTINCT DATE(reading_time) as analysis_date')
        //     ->orderBy('analysis_date')
        //     ->pluck('analysis_date');

        // $dates = PrepaidToken::selectRaw('DISTINCT DATE(purchase_date) as analysis_date')
        //     ->orderBy('analysis_date')
        //     ->pluck('analysis_date');

        $dates = [
            '2026-02-25',
        ];

        foreach ($dates as $date) {

            ProcessAmiAnalysis::dispatch($date);
            // ProcessPrepaidAnalysis::dispatch($date);

            $this->info("Job dispatched for {$date}");
        }

        $this->info("All jobs dispatched!");
    }
}
