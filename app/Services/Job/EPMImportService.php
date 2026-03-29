<?php

namespace App\Services\Job;

use Illuminate\Support\Facades\Log;
use App\Helpers\CsvHelper;
use App\Helpers\DateHelper;
use App\Helpers\NumberHelper;
use App\Models\Inspection;
use App\Models\Meters;
use App\Models\UploadHistory;

class EPMImportService
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

        Log::info("Import EPM selesai: " . $filePath);
        $this->updateHistory($historyId, $rowCount, 'success');
    }

    private function transformRow($row, $metersMap)
    {
        $customerNumber = trim($row['IDPEL'] ?? '');
        $meterId = $metersMap[$customerNumber] ?? null;
        if (!$meterId) return null;

        $inspectionTime = DateHelper::parse($row['WAKTU_PERIKSA'] ?? null);
        if (!$inspectionTime) return null;

        return [
            'meter_id'        => $meterId,
            'inspection_time' => $inspectionTime,
            'stand_lwbp'      => NumberHelper::safeFloat($row['STAND_LWBP'] ?? null),
            'stand_wbp'       => NumberHelper::safeFloat($row['STAND_WBP'] ?? null),
            'stand_kvarh'     => NumberHelper::safeFloat($row['STAND_KVARH'] ?? null),
            'officer_name'    => $row['NAMA_PETUGAS'] ?? null,
            'notes'           => $row['CATATAN'] ?? null,
            'source'          => 'EPM',

            'created_at'      => now(),
            'updated_at'      => now(),
        ];
    }

    private function flushBatch(&$batch)
    {
        $batch = collect($batch)
            ->keyBy(fn($item) => $item['meter_id'] . '|' . $item['inspection_time'])
            ->values()
            ->all();
            
        Inspection::upsert(
            $batch,
            ['meter_id', 'inspection_time'],
            [
                'stand_lwbp',
                'stand_wbp',
                'stand_kvarh',
                'officer_name',
                'notes',
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
