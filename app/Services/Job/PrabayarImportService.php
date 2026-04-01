<?php

namespace App\Services\Job;

use App\Models\Meters;
use App\Models\UploadHistory;
use App\Helpers\CsvHelper;
use App\Helpers\DateHelper;
use App\Helpers\NumberHelper;
use App\Models\PrepaidToken;
use Illuminate\Support\Facades\Log;

class PrabayarImportService
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

        Log::info("Import Prabayar selesai: " . $filePath);
        $this->updateHistory($historyId, $rowCount, 'success');
    }

    private function transformRow($row, $metersMap)
    {
        $customerNumber = trim($row['IDPEL'] ?? '');
        $meterId = $metersMap[$customerNumber] ?? null;
        if (!$meterId) return null;

        $purchaseDate = DateHelper::parse($row['TGLBAYAR'] ?? null);
        if (!$purchaseDate) return null;

        return [
            'meter_id' => $meterId,
            'token_number' => trim($row['NOMOR_TOKEN'] ?? ''),
            'purchase_date' => $purchaseDate,
            'kwh_purchased' => NumberHelper::safeFloat($row['PEMKWH'] ?? null),
            'amount_paid' => NumberHelper::safeFloat($row['RPPTL'] ?? null),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    private function flushBatch(&$batch)
    {
        PrepaidToken::upsert(
            $batch,
            ['token_number'],
            [
                'purchase_date',
                'kwh_purchased',
                'amount_paid',
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
