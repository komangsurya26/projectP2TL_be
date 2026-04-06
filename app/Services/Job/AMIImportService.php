<?php

namespace App\Services\Job;

use App\Models\MeterReading;
use App\Models\Meters;
use App\Models\UploadHistory;
use Illuminate\Support\Facades\Log;
use App\Helpers\NumberHelper;
use App\Helpers\CsvHelper;
use App\Helpers\DateHelper;

class AMIImportService
{
    public function process($filePath, $historyId)
    {
        if (!file_exists($filePath)) {
            Log::error("File tidak ditemukan: " . $filePath);
            return $this->updateHistory($historyId, 0, 'error');
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            Log::error("Tidak bisa membuka file: " . $filePath);
            return $this->updateHistory($historyId, 0, 'error');
        }

        try {
            $delimiter = CsvHelper::detectDelimiter($filePath);
            $fileHeader = fgetcsv($handle, 0, $delimiter);

            if (!$fileHeader) {
                throw new \Exception("CSV kosong atau header tidak terbaca");
            }

            $this->processRows($handle, $fileHeader, $delimiter, $historyId, $filePath);
        } catch (\Exception $e) {
            Log::error("Error proses import: " . $e->getMessage());
            $this->updateHistory($historyId, 0, 'error');
        } finally {
            if ($handle) fclose($handle);
        }
    }

    public function processRows($handle, $headers, $delimiter, $historyId, $filePath)
    {
        $metersMap = Meters::pluck('id', 'idpel')->toArray();

        $batch = [];
        $chunkSize = 1000;
        $rowCount = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (empty($row)) continue;

            $row = CsvHelper::normalizeRow($row, $headers);
            if (!$row) continue;

            $transformed = $this->transformRow($row, $metersMap);
            if (!$transformed) continue;

            $batch[] = $transformed;
            $rowCount++;

            if (count($batch) >= $chunkSize) {
                $this->flushBatch($batch);
            }
        }

        if (!empty($batch)) {
            $this->flushBatch($batch);
        }

        Log::info("Import AMI selesai: " . $filePath);
        $this->updateHistory($historyId, $rowCount, 'success');
    }

    private function transformRow($row, $metersMap)
    {
        $customerNumber = trim($row['CUSTOMER_NUMBER'] ?? '');
        $meterId = $metersMap[$customerNumber] ?? null;
        if (!$meterId) return null;

        $dataTime = DateHelper::parse($row['DATA_TIME'] ?? null);
        if (!$dataTime) return null;

        return [
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

            'kwh_total' => NumberHelper::safeFloat($row['IMPORT_ACTIVE_ENERGY_SEND'] ?? null),
            'kvarh_total' => NumberHelper::safeFloat($row['IMPORT_TOTAL_REACTIVE_ENERGY'] ?? null),
            'power_kw' => NumberHelper::safeFloat($row['IM_AC_P'] ?? null),

            'power_factor' => NumberHelper::safeFloat($row['PF'] ?? null),
            'apparent_power' => NumberHelper::safeFloat($row['APPARENT_POWER_SEND'] ?? null),

            'source' => 'AMI',

            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    private function flushBatch(&$batch)
    {
        MeterReading::upsert(
            $batch,
            ['meter_id', 'reading_time', 'source'],
            [
                'voltage_r',
                'voltage_s',
                'voltage_t',
                'current_r',
                'current_s',
                'current_t',
                'import_kwh',
                'export_kwh',
                'kwh_total',
                'kvarh_total',
                'power_kw',
                'power_factor',
                'apparent_power',
                'updated_at'
            ]
        );

        Log::info("Batch inserted: " . count($batch));
        $batch = [];
    }

    private function updateHistory($historyId, $rows, $status)
    {
        $history = UploadHistory::find($historyId);
        if ($history) {
            $history->update([
                'rows' => $rows,
                'status' => $status
            ]);
        }
    }
}
