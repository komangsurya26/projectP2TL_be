<?php

namespace App\Jobs;

use App\Models\Pelanggan;
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

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    private function safeFloat($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? (float) $value : null;
    }

    private function detectDelimiter($filePath)
    {
        $delimiters = ["\t", ";", ","];
        $counts = [];

        $handle = fopen($filePath, "r");
        $firstLine = fgets($handle);
        fclose($handle);

        foreach ($delimiters as $delimiter) {
            $counts[$delimiter] = substr_count($firstLine, $delimiter);
        }

        return array_search(max($counts), $counts);
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

        // header manual sesuai urutan file DIL
        $header = [
            'idpel',
            'nama',
            'tarif',
            'daya',
            'nomor_meter_kwh',
            'nomor_gardu',
            'nama_up',
            'unitup',
            'koordinat_x',
            'koordinat_y',
            'notelp_hp',
            'kdpembmeter'
        ];

        $delimiter = $this->detectDelimiter($this->filePath);

        $map = [
            'L' => 'AMI',
            'P' => 'PRABAYAR',
            'A' => 'AMR'
        ];

        $batch = [];
        $chunkSize = 5000;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {

            if (empty($row)) {
                continue;
            }

            // samakan jumlah kolom
            $row = array_pad($row, count($header), null);

            if (count($row) > count($header)) {
                $row = array_slice($row, 0, count($header));
            }

            $row = array_combine($header, $row);

            if (!$row) {
                continue;
            }

            $idpel = trim($row['idpel'] ?? '');

            if (!$idpel) {
                continue;
            }

            $kode = strtoupper(trim($row['kdpembmeter'] ?? ''));
            $meterType = $map[$kode] ?? 'MANUAL';

            $batch[] = [
                'idpel' => $idpel,
                'nama' => trim($row['nama'] ?? ''),
                'tarif' => $row['tarif'] ?? null,
                'daya' => $this->safeFloat($row['daya'] ?? null),
                'nometer' => trim($row['nomor_meter_kwh'] ?? ''),
                'alamat' => $row['nama_up'] ?? null,
                'unitup' => $row['unitup'] ?? null,
                'koordinat_x' => $this->safeFloat($row['koordinat_x'] ?? null),
                'koordinat_y' => $this->safeFloat($row['koordinat_y'] ?? null),
                'notelp' => trim($row['notelp_hp'] ?? ''),
                'jenis_meter' => $meterType
            ];

            // insert batch
            if (count($batch) >= $chunkSize) {

                Pelanggan::upsert(
                    $batch,
                    ['idpel'],
                    [
                        'nama',
                        'tarif',
                        'daya',
                        'nometer',
                        'alamat',
                        'unitup',
                        'koordinat_x',
                        'koordinat_y',
                        'notelp',
                        'jenis_meter'
                    ]
                );

                Log::info("Batch inserted: " . count($batch));

                $batch = [];
            }
        }

        // insert sisa batch
        if (!empty($batch)) {

            Pelanggan::upsert(
                $batch,
                ['idpel'],
                [
                    'nama',
                    'tarif',
                    'daya',
                    'nometer',
                    'alamat',
                    'unitup',
                    'koordinat_x',
                    'koordinat_y',
                    'notelp',
                    'jenis_meter'
                ]
            );

            Log::info("Final batch inserted: " . count($batch));
        }

        fclose($handle);

        Log::info("Import selesai: " . $this->filePath);
    }
}
