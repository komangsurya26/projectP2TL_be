<?php

namespace App\Jobs;

use App\Models\Pelanggan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDILImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batch;

    public function __construct(array $batch)
    {
        $this->batch = $batch;
    }

    /**
     * Convert value to float if numeric, else null
     */
    private function safeFloat($value)
    {
        return is_numeric($value) ? (float) $value : null;
    }

    public function handle()
    {
        $map = [
            'L' => 'AMI',
            'P' => 'PRABAYAR',
            'A' => 'AMR'
        ];

        foreach ($this->batch as $row) {
            // Tentukan jenis meter
            $meterType = 'MANUAL';
            if (!empty($row['produk_prabayar']) && strtoupper($row['produk_prabayar']) === 'PRABAYAR') {
                $meterType = 'PRABAYAR';
            } else {
                $kode = $row['kdpembmeter'] ?? null;
                $meterType = $map[$kode] ?? 'MANUAL'; // gunakan null jika tidak ada
            }

            // Ambil IDPEL
            $idpel = $row['idpel'] ?? null;
            if (!$idpel) {
                continue; // skip row
            }

            // Konversi numeric aman
            $daya = $this->safeFloat($row['daya'] ?? null);
            $koordinat_x = $this->safeFloat($row['koordinat_x'] ?? null);
            $koordinat_y = $this->safeFloat($row['koordinat_y'] ?? null);

            Pelanggan::updateOrCreate(
                ['idpel' => $idpel],
                [
                    'nama' => $row['nama'] ?? null,
                    'tarif' => $row['tarif'] ?? null,
                    'daya' => $daya,
                    'nometer' => $row['nomor_meter_kwh'] ?? $row['nomorkwh'] ?? null,
                    'alamat' => $row['nama_up'] ?? $row['pemda_keterangan'] ?? $row['alamat'] ?? null,
                    'unitup' => $row['unitup'] ?? null,
                    'koordinat_x' => $koordinat_x,
                    'koordinat_y' => $koordinat_y,
                    'notelp' => $row['notelp_hp'] ?? $row['no_hp'] ?? null,
                    'jenis_meter' => $meterType
                ]
            );
        }
    }
}
