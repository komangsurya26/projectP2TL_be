<?php

namespace App\Jobs;

use App\Models\MeterReading;
use App\Models\Meters;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Helpers\NumberHelper;
use App\Helpers\CsvHelper;

class ProcessAMIImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    public function handle()
    {
        if (!file_exists($this->filePath)) {
            Log::error("File tidak ditemukan: " . $this->filePath);
            return;
        }

        $handle = fopen($this->filePath, 'r');
        if (!$handle) {
            Log::error("Tidak bisa membuka file: " . $this->filePath);
            return;
        }

        $delimiter = CsvHelper::detectDelimiter($this->filePath);

        $fileHeader = fgetcsv($handle, 0, $delimiter);
        if (!$fileHeader) {
            Log::error("CSV kosong atau header tidak terbaca: " . $this->filePath);
            fclose($handle);
            return;
        }

        $metersMap = Meters::pluck('id', 'idpel')->toArray();

        $batch = [];
        $chunkSize = 4000;
        fgets($handle);

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (empty($row)) continue;

            $row = array_pad($row, count($fileHeader), null);
            $row = array_slice($row, 0, count($fileHeader));
            $row = array_combine($fileHeader, $row);
            if (!$row) continue;

            $customerNumber = trim($row['CUSTOMER_NUMBER'] ?? '');
            $meterId = $metersMap[$customerNumber] ?? null;
            if (!$meterId) continue;

            $dataTime = !empty($row['DATA_TIME']) ? date('Y-m-d H:i:s', strtotime($row['DATA_TIME'])) : null;
            if (!$dataTime) continue;

            $batch[] = [
                'meter_id' => $meterId,
                'reading_time' => $dataTime,
                'voltage_r' => NumberHelper::safeFloat($row['VOL_R'] ?? null),
                'voltage_s' => NumberHelper::safeFloat($row['VOL_S'] ?? null),
                'voltage_t' => NumberHelper::safeFloat($row['VOL_T'] ?? null),
                'current_r' => NumberHelper::safeFloat($row['CUR_R'] ?? null),
                'current_s' => NumberHelper::safeFloat($row['CUR_S'] ?? null),
                'current_t' => NumberHelper::safeFloat($row['CUR_T'] ?? null),
                'import_kwh' => NumberHelper::safeFloat($row['IMPORT_ACTIVE_ENERGY_SEND'] ?? null),
                'export_kwh' => NumberHelper::safeFloat($row['EXPORT_ACTIVE_ENERGY_RECEIVE'] ?? null),
                'power_factor' => NumberHelper::safeFloat($row['PF'] ?? null),
                'apparent_power' => NumberHelper::safeFloat($row['APPARENT_POWER_SEND'] ?? null),
                'created_at' => now(),
                'updated_at' => now()
            ];

            if (count($batch) >= $chunkSize) {
                MeterReading::upsert(
                    $batch,
                    ['meter_id', 'reading_time'],
                    [
                        'voltage_r',
                        'voltage_s',
                        'voltage_t',
                        'current_r',
                        'current_s',
                        'current_t',
                        'import_kwh',
                        'export_kwh',
                        'power_factor',
                        'apparent_power',
                        'updated_at'
                    ]
                );
                Log::info("Batch inserted: " . count($batch));
                $batch = [];
            }
        }

        if (!empty($batch)) {
            MeterReading::upsert(
                $batch,
                ['meter_id', 'reading_time'],
                [
                    'voltage_r',
                    'voltage_s',
                    'voltage_t',
                    'current_r',
                    'current_s',
                    'current_t',
                    'import_kwh',
                    'export_kwh',
                    'power_factor',
                    'apparent_power',
                    'updated_at'
                ]
            );
            Log::info("Final batch inserted: " . count($batch));
        }

        fclose($handle);
        Log::info("Import AMI selesai: " . $this->filePath);
    }
}
