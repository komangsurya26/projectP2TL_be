<?php

namespace App\Services\Job;

use Illuminate\Support\Facades\Log;
use App\Helpers\CsvHelper;
use App\Helpers\DateHelper;
use App\Helpers\NumberHelper;
use App\Models\Inspection;
use App\Models\InspectionLocations;
use App\Models\InspectionMeasurements;
use App\Models\Meters;
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
            'inspection' => [
                'meter_id'        => $meterId,
                'inspection_time' => $inspectionTime,

                'stand_lwbp'  => NumberHelper::safeFloat($row['STAND_LWBP'] ?? null),
                'stand_wbp'   => NumberHelper::safeFloat($row['STAND_WBP'] ?? null),
                'stand_kvarh' => NumberHelper::safeFloat($row['STAND_KVARH'] ?? null),

                'status_kwh'  => $row['STATUS_KWH'] ?? null,
                'kode_pesan'  => $row['KODE_PESAN'] ?? null,
                'pemutusan'   => $row['PEMUTUSAN'] ?? null,
                'rupiah_ts'   => NumberHelper::safeFloat($row['RUPIAH_TS'] ?? null),

                'officer_name' => $row['NAMA_PETUGAS'] ?? null,
                'notes'        => $row['CATATAN'] ?? null,
                'source'       => 'EPM',

                'created_at' => now(),
                'updated_at' => now(),
            ],

            'measurement' => [
                'voltage_r' => NumberHelper::safeFloat($row['TEGANGAN_R_N'] ?? null),
                'voltage_s' => NumberHelper::safeFloat($row['TEGANGAN_S_N'] ?? null),
                'voltage_t' => NumberHelper::safeFloat($row['TEGANGAN_T_N'] ?? null),

                'current_r' => NumberHelper::safeFloat($row['ARUS_METER'] ?? null),
                'current_s' => null,
                'current_t' => null,

                'power_factor' => NumberHelper::safeFloat($row['COS_BEBAN_R'] ?? null),
                'deviasi'      => NumberHelper::safeFloat($row['DEVIASI'] ?? null),
                'faktor_kali'  => NumberHelper::safeFloat($row['FAKTOR_KALI_KWH'] ?? null),
            ],

            'location' => [
                'latitude'  => NumberHelper::safeFloat($row['LATITUDE'] ?? null),
                'longitude' => NumberHelper::safeFloat($row['LONGITUDE'] ?? null),
                'gardu'     => $row['GARDU'] ?? null,
                'tiang'     => $row['TIANG'] ?? null,
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
                'officer_name',
                'notes',
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
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $locationBatch[$inspectionId] = [
                ...$item['location'],
                'inspection_id' => $inspectionId,
                'created_at' => now(),
                'updated_at' => now(),
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
                'gardu',
                'tiang',
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
