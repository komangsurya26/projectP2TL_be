<?php

namespace App\Services\Job;

use App\Helpers\CoordinateHelper;
use App\Models\Pelanggan;
use App\Models\Meters;
use App\Models\UploadHistory;
use App\Helpers\NumberHelper;
use App\Helpers\CsvHelper;
use Illuminate\Support\Facades\Log;

class DILImportService
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

    private function processRows($handle, $fileHeader, $delimiter, $historyId, $filePath)
    {
        $map = [
            'L' => 'AMI',
            'P' => 'PRABAYAR',
            'A' => 'AMR'
        ];

        $pelangganBatch = [];
        $meterBatch = [];
        $chunkSize = 3000;
        $seenIdpel = [];
        $rowCount = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (empty($row)) continue;

            $row = CsvHelper::normalizeRow($row, $fileHeader);
            if (!$row) continue;

            $idpel = trim($row['IDPEL'] ?? '');
            if (!$idpel || isset($seenIdpel[$idpel])) continue;

            $seenIdpel[$idpel] = true;

            [$pelanggan, $meter] = $this->transformRow($row, $map);

            $pelangganBatch[] = $pelanggan;
            $meterBatch[] = $meter;

            $rowCount++;

            if (count($pelangganBatch) >= $chunkSize) {
                $this->flushBatch($pelangganBatch, $meterBatch);
            }
        }

        if (!empty($pelangganBatch)) {
            $this->flushBatch($pelangganBatch, $meterBatch);
        }

        Log::info("Import DIL selesai: " . $filePath);
        $this->updateHistory($historyId, $rowCount, 'success');
    }

    private function transformRow($row, $map)
    {
        $idpel = trim($row['IDPEL']);

        // get meter type
        $kode = strtoupper(trim($row['KDPEMBMETER'] ?? ''));
        $meterType = $map[$kode] ?? 'MANUAL';

        // menukar jika koordinat x < 0 dari data csv
        $x = $row['KOORDINAT_X'] ?? null;
        $y = $row['KOORDINAT_Y'] ?? null;
        $x = $x !== null ? strval($x) : null;
        $y = $y !== null ? strval($y) : null;
        if ($x !== null && is_numeric($x) && floatval($x) < 0) {
            [$x, $y] = [$y, $x];
        }
        $longitude = CoordinateHelper::normalizeLongitude($x);
        $latitude  = CoordinateHelper::normalizeLatitude($y);

        // insert pelanggan
        $pelanggan = [
            'idpel' => $idpel,
            'nama' => trim($row['NAMA'] ?? ''),
            'notelp' => trim($row['NOTELP_HP'] ?? '') == '' ? null : trim($row['NOTELP_HP']),
            'alamat' => $row['NAMA_UP'] ?? null,
            'unitup' => $row['UNITUP'] ?? null,
            'longitude' => $longitude,
            'latitude' => $latitude,
            'created_at' => now(),
            'updated_at' => now()
        ];

        // insert meter
        $meter = [
            'idpel' => $idpel,
            'meter_type' => $meterType,
            'tariff' => $row['TARIF'] ?? null,
            'power_capacity' => NumberHelper::safeFloat($row['DAYA'] ?? null),
            'created_at' => now(),
            'updated_at' => now()
        ];

        return [$pelanggan, $meter];
    }

    private function flushBatch(&$pelangganBatch, &$meterBatch)
    {
        Pelanggan::upsert(
            $pelangganBatch,
            ['idpel'],
            ['nama', 'notelp', 'alamat', 'unitup', 'longitude', 'latitude', 'updated_at']
        );

        Meters::upsert(
            $meterBatch,
            ['idpel'],
            ['meter_type', 'tariff', 'power_capacity', 'updated_at']
        );

        Log::info("Batch inserted: " . count($pelangganBatch));

        $pelangganBatch = [];
        $meterBatch = [];
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
