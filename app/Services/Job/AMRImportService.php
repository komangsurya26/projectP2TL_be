<?php

namespace App\Services\Job;

use App\Models\MeterReading;
use App\Models\UploadHistory;
use Illuminate\Support\Facades\Log;
use App\Helpers\NumberHelper;
use App\Helpers\CsvHelper;
use App\Helpers\DateHelper;
use App\Models\Meters;

class AMRImportService
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

            fclose($handle);
        } catch (\Exception $e) {
            Log::error("Error proses import: " . $e->getMessage());
            $this->updateHistory($historyId, 0, 'error');
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

        Log::info("Import AMR selesai: " . $filePath);
        $this->updateHistory($historyId, $rowCount, 'success');
    }

    private function transformRow($row, $metersMap)
    {
        $customerNumber = trim($row['LOCATION_CODE'] ?? '');
        $meterId = $metersMap[$customerNumber] ?? null;
        if (!$meterId) return null;

        $dataTime = DateHelper::parse($row['READ_DATE'] ?? null);
        if (!$dataTime) return null;

        return [
            'meter_id' => $meterId,
            'reading_time' => $dataTime,
            'voltage_r' => NumberHelper::safeFloat($row['VOLTAGE_L1'] ?? null),
            'voltage_s' => NumberHelper::safeFloat($row['VOLTAGE_L2'] ?? null),
            'voltage_t' => NumberHelper::safeFloat($row['VOLTAGE_L3'] ?? null),
            'current_r' => NumberHelper::safeFloat($row['CURRENT_L1'] ?? null),
            'current_s' => NumberHelper::safeFloat($row['CURRENT_L2'] ?? null),
            'current_t' => NumberHelper::safeFloat($row['CURRENT_L3'] ?? null),
            'import_kwh' => NumberHelper::safeFloat($row['KWH_IMPORT_TOTAL'] ?? null),
            'export_kwh' => NumberHelper::safeFloat($row['KWH_EXPORT_TOTAL'] ?? null),
            'kwh_total' => NumberHelper::safeFloat($row['KWH_IMPORT_TOTAL'] ?? null),
            'kvarh_total' => NumberHelper::safeFloat($row['KVARH_IMPORT_TOTAL'] ?? null),
            'power_kw' => NumberHelper::safeFloat($row['ACTIVE_POWER_TOTAL'] ?? null),
            'power_factor' => NumberHelper::safeFloat($row['POWER_FACTOR_TOTAL'] ?? null),
            'apparent_power' => NumberHelper::safeFloat($row['APPARENT_POWER_TOTAL'] ?? null),
            'source' => 'AMR',
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
