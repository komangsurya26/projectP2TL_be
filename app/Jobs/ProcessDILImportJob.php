<?php

namespace App\Jobs;

use App\Models\Pelanggan;
use App\Models\Meters;
use App\Helpers\NumberHelper;
use App\Helpers\CsvHelper;
use App\Models\UploadHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDILImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $historyId;

    public function __construct($filePath, $historyId)
    {
        $this->filePath = $filePath;
        $this->historyId = $historyId;
    }

    public function handle()
    {
        if (!file_exists($this->filePath)) {
            Log::error("File tidak ditemukan: " . $this->filePath);
            $this->updateHistory(0, 'error');
            return;
        }

        $handle = fopen($this->filePath, 'r');
        if (!$handle) {
            Log::error("Tidak bisa membuka file: " . $this->filePath);
            $this->updateHistory(0, 'error');
            return;
        }

        try {
            $delimiter = CsvHelper::detectDelimiter($this->filePath);
            $fileHeader = fgetcsv($handle, 0, $delimiter);
            if (!$fileHeader) {
                throw new \Exception("CSV kosong atau header tidak terbaca");
            }

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

                $row = array_pad($row, count($fileHeader), null);
                $row = array_slice($row, 0, count($fileHeader));
                $row = array_combine($fileHeader, $row);
                if (!$row) continue;

                $idpel = trim($row['IDPEL'] ?? '');
                if (!$idpel || isset($seenIdpel[$idpel])) continue;
                $seenIdpel[$idpel] = true;

                $kode = strtoupper(trim($row['KDPEMBMETER'] ?? ''));
                $meterType = $map[$kode] ?? 'MANUAL';

                $rawMeterNumber = trim($row['NOMOR_METER_KWH'] ?? '');
                if ($rawMeterNumber === '-') $rawMeterNumber = '';
                $meterNumber = $rawMeterNumber ?: 'TEMP-' . $idpel;
                $isTemporary = empty($rawMeterNumber);

                $pelangganBatch[] = [
                    'idpel' => $idpel,
                    'nama' => trim($row['NAMA'] ?? ''),
                    'notelp' => trim($row['NOTELP_HP'] ?? ''),
                    'alamat' => $row['NAMA_UP'] ?? null,
                    'unitup' => $row['UNITUP'] ?? null,
                    'koordinat_x' => NumberHelper::safeFloat($row['KOORDINAT_X'] ?? null),
                    'koordinat_y' => NumberHelper::safeFloat($row['KOORDINAT_Y'] ?? null),
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                $meterBatch[$idpel] = [
                    'idpel' => $idpel,
                    'meter_number' => $meterNumber,
                    'meter_type' => $meterType,
                    'tariff' => $row['TARIF'] ?? null,
                    'power_capacity' => NumberHelper::safeFloat($row['DAYA'] ?? null),
                    'is_temporary' => $isTemporary,
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                $rowCount++; // hitung jumlah baris upload history

                if (count($pelangganBatch) >= $chunkSize) {
                    Pelanggan::upsert(
                        $pelangganBatch,
                        ['idpel'],
                        ['nama', 'notelp', 'alamat', 'unitup', 'koordinat_x', 'koordinat_y', 'updated_at']
                    );

                    Meters::upsert(
                        array_values($meterBatch),
                        ['idpel', 'meter_number'],
                        ['meter_type', 'tariff', 'power_capacity', 'is_temporary', 'updated_at']
                    );

                    Log::info("Batch inserted: " . count($pelangganBatch));

                    $pelangganBatch = [];
                    $meterBatch = [];
                }
            }

            // Sisa batch terakhir
            if (!empty($pelangganBatch)) {
                Pelanggan::upsert(
                    $pelangganBatch,
                    ['idpel'],
                    ['nama', 'notelp', 'alamat', 'unitup', 'koordinat_x', 'koordinat_y', 'updated_at']
                );

                Meters::upsert(
                    array_values($meterBatch),
                    ['idpel', 'meter_number'],
                    ['meter_type', 'tariff', 'power_capacity', 'is_temporary', 'updated_at']
                );

                Log::info("Final batch inserted: " . count($pelangganBatch));
            }

            fclose($handle);
            Log::info("Import DIL selesai: " . $this->filePath);

            $this->updateHistory($rowCount, 'success');
        } catch (\Exception $e) {
            Log::error("Error proses import: " . $e->getMessage());
            $this->updateHistory(0, 'error');
        }
    }

    protected function updateHistory(int $rows, string $status)
    {
        $history = UploadHistory::find($this->historyId);
        if ($history) {
            $history->update([
                'rows' => $rows,
                'status' => $status
            ]);
        }
    }
}
