<?php

namespace App\Jobs;

use App\Models\MeterAnalysis;
use App\Models\PrepaidToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessPrepaidAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $date;
    protected $batchSize;

    public function __construct($date, $batchSize = 5000)
    {
        $this->date = $date;
        $this->batchSize = $batchSize;
    }

    public function handle()
    {
        $data = PrepaidToken::select(
            'meter_id',
            DB::raw('SUM(kwh_purchased) as consumption_kwh')
        )
            ->whereDate('purchase_date', $this->date)
            ->groupBy('meter_id')
            ->cursor();

        $batch = [];

        foreach ($data as $row) {

            $batch[] = [
                'meter_id' => $row->meter_id,
                'analysis_date' => $this->date,
                'consumption_kwh' => $row->consumption_kwh,
                'anomaly_status' => $this->detect($row->consumption_kwh),
                'analysis_method' => 'prepaid_rule',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= $this->batchSize) {
                $this->upsert($batch);
                $batch = [];
            }
        }

        if ($batch) $this->upsert($batch);
    }

    private function detect($kwh)
    {
        if ($kwh <= 0) return 'LOW_CONSUMPTION';
        if ($kwh > 300) return 'HIGH_TOPUP';
        return 'NORMAL';
    }

    private function upsert($batch)
    {
        MeterAnalysis::upsert(
            $batch,
            ['meter_id', 'analysis_date'],
            ['consumption_kwh', 'anomaly_status', 'analysis_method', 'updated_at']
        );
    }
}
