<?php

namespace App\Services\Job;

use App\Models\Meters;
use App\Models\UploadHistory;
use App\Helpers\CsvHelper;
use App\Helpers\NumberHelper;
use App\Models\BillingRecord;
use Illuminate\Support\Facades\Log;

class SorekImportService
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

    private function processRows($handle, $headers, $delimiter, $historyId, $filePath)
    {
        $metersMap = Meters::pluck('id', 'idpel')->toArray();

        $batch = [];
        $chunkSize = 3000;
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

        Log::info("Import Sorek selesai: " . $filePath);
        $this->updateHistory($historyId, $rowCount, 'success');
    }

    private function transformRow($row, $metersMap)
    {
        $customerNumber = trim($row['IDPEL'] ?? '');
        $meterId = $metersMap[$customerNumber] ?? null;
        if (!$meterId) return null;

        $periode = trim($row['THBLREK'] ?? '');
        if (!$periode) return null;

        $month = (int) substr($periode, 4, 2);
        $year = (int) substr($periode, 0, 4);

        $kwhTotal = NumberHelper::safeFloat($row['PEMKWH'] ?? 0);
        $tagihan = NumberHelper::safeFloat($row['RPTAG'] ?? 0);

        return [
            'meter_id' => $meterId,
            'periode' => $periode,

            'month' => $month,
            'year' => $year,

            'kwh_lwbp' => NumberHelper::safeFloat($row['KWHLWBP'] ?? 0),
            'kwh_wbp' => NumberHelper::safeFloat($row['KWHWBP'] ?? 0),
            'kwh_total' => $kwhTotal,
            'kvarh' => NumberHelper::safeFloat($row['KVARH'] ?? 0),

            'rpptl' => NumberHelper::safeFloat($row['RPPTL'] ?? 0),
            'rpppn' => NumberHelper::safeFloat($row['RPPPN'] ?? 0),
            'rpbpju' => NumberHelper::safeFloat($row['RPBPJU'] ?? 0),
            'tagihan' => $tagihan,

            'status' => $row['DLPD'] ?? null,

            'cost_per_kwh' => $kwhTotal > 0
                ? $tagihan / $kwhTotal
                : null,

            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    private function flushBatch(&$batch)
    {
        $data = collect($batch)
            ->keyBy(fn($item) => $item['meter_id'] . '|' . $item['periode'])
            ->values()
            ->all();

        BillingRecord::upsert(
            $data,
            ['meter_id', 'periode'],
            [
                'kwh_lwbp',
                'kwh_wbp',
                'kwh_total',
                'kvarh',
                'rpptl',
                'rpppn',
                'rpbpju',
                'tagihan',
                'status',
                'cost_per_kwh',
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
