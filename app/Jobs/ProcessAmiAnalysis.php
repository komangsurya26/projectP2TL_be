<?php

namespace App\Jobs;

use App\Models\MeterAnalysis;
use App\Models\MeterReading;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Analysis\Analyzer;
use App\Services\Analysis\HistoricalService;

class ProcessAmiAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $date;
    protected $batchSize;

    public function __construct($date, $batchSize = 5000)
    {
        $this->date = $date;
        $this->batchSize = $batchSize; // batch per iterasi
    }

    public function handle()
    {
        $analyzer = new Analyzer();
        $historical = new HistoricalService();

        // 1️⃣ Ambil semua meter + konsumsi hari ini
        $meterReadings = MeterReading::selectRaw('meter_id, MAX(import_kwh) - MIN(import_kwh) as consumption_kwh')
            ->whereDate('reading_time', $this->date)
            ->groupBy('meter_id')
            ->get(); // ambil semua sekaligus (memory aman kalau jumlah meter wajar)

        $batch = [];
        foreach ($meterReadings as $row) {
            $avg7 = $historical->getAverage($row->meter_id, $this->date, 7) ?? 0;
            $avg30 = $historical->getAverage($row->meter_id, $this->date, 30) ?? 0;
            $zeroDays = $historical->getZeroDays($row->meter_id, $this->date, 3) ?? 0;

            $result = $analyzer->analyze([
                'meter_id' => $row->meter_id,
                'consumption_kwh' => $row->consumption_kwh,
                'avg_7_days' => $avg7,
                'avg_30_days' => $avg30,
                'zero_days_count' => $zeroDays,
            ]);

            $status = $result['status'];

            $batch[] = [
                'meter_id' => $row->meter_id,
                'analysis_date' => $this->date,
                'consumption_kwh' => $row->consumption_kwh,
                'avg_7_days' => $avg7,
                'avg_30_days' => $avg30,
                'anomaly_status' => $status,
                'anomaly_score' => $result['score'],
                'flags' => json_encode($result['flags']),
                'analysis_method' => 'ami_historical',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= $this->batchSize) {
                MeterAnalysis::upsert(
                    $batch,
                    ['meter_id', 'analysis_date'],
                    ['consumption_kwh', 'avg_7_days', 'avg_30_days', 'anomaly_status', 'anomaly_score', 'flags', 'analysis_method', 'updated_at']
                );
                $batch = [];
            }
        }

        // Insert sisa batch terakhir
        if (!empty($batch)) {
            MeterAnalysis::upsert(
                $batch,
                ['meter_id', 'analysis_date'],
                ['consumption_kwh', 'avg_7_days', 'avg_30_days', 'anomaly_status', 'anomaly_score', 'flags', 'analysis_method', 'updated_at']
            );
        }

        \Log::info("Analysis done for date {$this->date}, total meters: " . count($meterReadings));
    }
}
