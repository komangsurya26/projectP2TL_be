<?php

namespace App\Services\Job;

use App\Helpers\CoordinateHelper;
use Illuminate\Support\Facades\Log;
use App\Helpers\CsvHelper;
use App\Helpers\DateHelper;
use App\Helpers\NumberHelper;
use App\Models\Inspection;
use App\Models\InspectionLocations;
use App\Models\InspectionMeasurements;
use App\Models\Meters;
use App\Models\Pelanggan;
use App\Models\UploadHistory;
use Carbon\Carbon;

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

        $cosBebanR = NumberHelper::safeFloat($row['COS_BEBAN_R'] ?? null);
        $cosBebanS = NumberHelper::safeFloat($row['COS_BEBAN_S'] ?? null);
        $cosBebanT = NumberHelper::safeFloat($row['COS_BEBAN_T'] ?? null);

        $cosValues = array_filter([
            $cosBebanR,
            $cosBebanS,
            $cosBebanT
        ], fn($v) => $v !== null);

        $powerFactor = count($cosValues)
            ? array_sum($cosValues) / count($cosValues)
            : null;

        return [
            'pelanggan' => [
                'idpel' => $customerNumber,
                'peruntukan' => trim($row['PERUNTUKAN'] ?? '') ?: null,
                'nama' => trim($row['NAMA'] ?? '') ?: null,
                'updated_at' => now(),
            ],
            'inspection' => [
                'meter_id'        => $meterId,
                'inspection_time' => $inspectionTime,

                'stand_lwbp'  => NumberHelper::safeFloat($row['STAND_LWBP'] ?? null),
                'stand_wbp'   => NumberHelper::safeFloat($row['STAND_WBP'] ?? null),
                'stand_kvarh' => NumberHelper::safeFloat($row['STAND_KVARH'] ?? null),

                'status_kwh'  => trim($row['STATUS_KWH'] ?? '') ?: null,
                'kode_pesan'  => trim($row['KODE_PESAN'] ?? '') ?: null,
                'pemutusan'   => trim($row['PEMUTUSAN'] ?? '') ?: null,
                'rupiah_ts'   => NumberHelper::safeFloat($row['RUPIAH_TS'] ?? null),

                'notes'        => trim($row['CATATAN'] ?? '') ?: null,
                'source'       => 'EPM',

                'created_at' => now(),
                'updated_at' => now(),
            ],

            'measurement' => [
                'voltage_r' => NumberHelper::safeFloat($row['TEGANGAN_R_N'] ?? null),
                'voltage_s' => NumberHelper::safeFloat($row['TEGANGAN_S_N'] ?? null),
                'voltage_t' => NumberHelper::safeFloat($row['TEGANGAN_T_N'] ?? null),

                'current_r' => NumberHelper::safeFloat($row['BEBAN_SEKUNDER_R'] ?? null),
                'current_s' => NumberHelper::safeFloat($row['BEBAN_SEKUNDER_S'] ?? null),
                'current_t' => NumberHelper::safeFloat($row['BEBAN_SEKUNDER_T'] ?? null),

                'power_factor' => $powerFactor,
                'deviasi'      => NumberHelper::safeFloat($row['DEVIASI'] ?? null),
                'faktor_kali'  => NumberHelper::safeFloat($row['FAKTOR_KALI_KWH'] ?? null),
            ],

            'location' => [
                'latitude'  => CoordinateHelper::normalizeLatitude($row['LATITUDE'] ?? null),
                'longitude' => CoordinateHelper::normalizeLongitude($row['LONGITUDE'] ?? null),
            ]
        ];
    }

    private function flushBatch(&$batch)
    {
        $inspectionData = collect($batch)
            ->keyBy(fn($item) => $item['inspection']['meter_id'] . '|' .
                Carbon::parse($item['inspection']['inspection_time'])->format('Y-m-d H:i:s'))
            ->pluck('inspection')
            ->values()
            ->all();

        $pelangganData = collect($batch)
            ->keyBy(fn($item) => $item['pelanggan']['idpel'])
            ->pluck('pelanggan')
            ->values()
            ->all();

        Inspection::upsert(
            $inspectionData,
            ['meter_id', 'inspection_time'],
            [
                'stand_lwbp',
                'stand_wbp',
                'stand_kvarh',
                'status_kwh',
                'kode_pesan',
                'pemutusan',
                'rupiah_ts',
                'notes',
                'updated_at'
            ]
        );

        Pelanggan::upsert(
            $pelangganData,
            ['idpel'],
            [
                'peruntukan',
                'nama',
                'updated_at'
            ]
        );

        $meterIds = collect($inspectionData)->pluck('meter_id')->unique();
        $times = collect($inspectionData)->pluck('inspection_time')->unique();

        $inspections = Inspection::whereIn('meter_id', $meterIds)
            ->whereIn('inspection_time', $times)
            ->get()
            ->keyBy(fn($i) => $i->meter_id . '|' . $i->inspection_time);

        $measurementBatch = [];
        $locationBatch = [];

        foreach ($batch as $item) {
            $key = $item['inspection']['meter_id'] . '|' . $item['inspection']['inspection_time'];

            if (!isset($inspections[$key])) continue;

            $inspectionId = $inspections[$key]->id;

            $measurementBatch[$inspectionId] = [
                ...$item['measurement'],
                'inspection_id' => $inspectionId,
            ];

            $locationBatch[$inspectionId] = [
                ...$item['location'],
                'inspection_id' => $inspectionId,
            ];
        }

        InspectionMeasurements::upsert(
            array_values($measurementBatch),
            ['inspection_id'],
            [
                'voltage_r',
                'voltage_s',
                'voltage_t',
                'current_r',
                'current_s',
                'current_t',
                'power_factor',
                'deviasi',
                'faktor_kali',
                'updated_at'
            ]
        );

        InspectionLocations::upsert(
            array_values($locationBatch),
            ['inspection_id'],
            [
                'latitude',
                'longitude',
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
