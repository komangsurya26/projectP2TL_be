<?php

namespace App\Jobs;

use App\Models\Measurement;
use App\Models\Pelanggan;
use App\Services\AMIAnalyzer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAMIAnalyzerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batch;

    public function __construct(array $batch)
    {
        $this->batch = $batch;
    }

    public function handle()
    {
        $measurements = [];
        $analyzer = new AMIAnalyzer();

        foreach ($this->batch as $row) {
            $pelanggan = Pelanggan::where('idpel', $row['customer_number'])->first();
            if (!$pelanggan) continue;

            $waktu = \Carbon\Carbon::parse($row['data_time']);

            // Cek duplicate
            if (Measurement::where('idpel', $pelanggan->idpel)->where('waktu_data', $waktu)->exists()) {
                continue;
            }

            $measurements[] = [
                'idpel' => $pelanggan->idpel,
                'jenis_meter' => 'AMI',
                'waktu_data' => $waktu,
                'voltage_r' => $row['voltage_r'],
                'voltage_s' => $row['voltage_s'],
                'voltage_t' => $row['voltage_t'],
                'current_r' => $row['current_r'],
                'current_s' => $row['current_s'],
                'current_t' => $row['current_t'],
                'pf' => $row['pf'],
                'energy_import' => $row['energy_import'],
                'energy_export' => $row['energy_export'],
                'reactive_import' => $row['reactive_import'],
                'reactive_export' => $row['reactive_export'],
                'current_netral' => $row['current_netral'],
                'apparent_power' => $row['apparent_power'],
            ];
        }

        if (!empty($measurements)) {
            Measurement::insert($measurements);

            // Analisis per row
            foreach ($measurements as $m) {
                $analyzer->analyze([
                    'idpel' => $m['idpel'],
                    'voltage_r' => $m['voltage_r'],
                    'voltage_s' => $m['voltage_s'],
                    'voltage_t' => $m['voltage_t'],
                    'current_r' => $m['current_r'],
                    'current_s' => $m['current_s'],
                    'current_t' => $m['current_t'],
                    'pf' => $m['pf'],
                    'current_netral' => $m['current_netral'],
                ]);
            }
        }
    }
}
