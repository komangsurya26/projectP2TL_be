<?php

namespace App\Jobs;

use App\Models\MeterAnalysis;
use App\Models\MeterReading;
use App\Models\PrepaidToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessMeterAnalysis implements ShouldQueue
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
        $date = $this->date;

        // =====================
        // 1️⃣ AMI Meters
        // =====================
        $ami = MeterReading::select(
            'meter_id',
            DB::raw('MAX(import_kwh) - MIN(import_kwh) as consumption_kwh')
        )
            ->whereDate('reading_time', $date)
            ->groupBy('meter_id')
            ->cursor();

        $batch = [];
        foreach ($ami as $row) {
            $batch[] = [
                'meter_id' => $row->meter_id,
                'analysis_date' => $date,
                'consumption_kwh' => $row->consumption_kwh,
                'anomaly_status' => $row->consumption_kwh < 1 ? 'LOW_CONSUMPTION' : 'NORMAL',
                'anomaly_score' => null,
                'analysis_method' => 'ami_rule',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= $this->batchSize) {
                MeterAnalysis::upsert(
                    $batch,
                    ['meter_id', 'analysis_date'],
                    ['consumption_kwh', 'anomaly_status', 'anomaly_score', 'analysis_method', 'updated_at']
                );
                $batch = [];
            }
        }

        if (!empty($batch)) {
            MeterAnalysis::upsert(
                $batch,
                ['meter_id', 'analysis_date'],
                ['consumption_kwh', 'anomaly_status', 'anomaly_score', 'analysis_method', 'updated_at']
            );
        }

        // =====================
        // 2️⃣ Prepaid Meters
        // =====================
        $tokens = PrepaidToken::select(
            'meter_id',
            DB::raw('SUM(kwh_purchased) as consumption_kwh')
        )
            ->whereDate('purchase_date', $date)
            ->groupBy('meter_id')
            ->cursor();

        $batch = [];

        foreach ($tokens as $row) {

            $batch[] = [
                'meter_id' => $row->meter_id,
                'analysis_date' => $date,
                'consumption_kwh' => $row->consumption_kwh,
                'anomaly_status' => $row->consumption_kwh <= 0 ? 'LOW_CONSUMPTION' : 'NORMAL',
                'anomaly_score' => null,
                'analysis_method' => 'prepaid_rule',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= $this->batchSize) {

                MeterAnalysis::upsert(
                    $batch,
                    ['meter_id', 'analysis_date'],
                    ['consumption_kwh', 'anomaly_status', 'anomaly_score', 'analysis_method', 'updated_at']
                );

                $batch = [];
            }
        }

        if (!empty($batch)) {

            MeterAnalysis::upsert(
                $batch,
                ['meter_id', 'analysis_date'],
                ['consumption_kwh', 'anomaly_status', 'anomaly_score', 'analysis_method', 'updated_at']
            );
        }
    }
}
