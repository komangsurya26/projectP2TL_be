<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Jobs\ProcessAMIAnalyzerJob;

class AMIImport implements ToCollection, WithHeadingRow, WithChunkReading, ShouldQueue
{
    public function collection(Collection $rows)
    {
        $batch = [];

        foreach ($rows as $row) {
            $batch[] = [
                'customer_number' => $row['customer_number'],
                'data_time' => $row['data_time'],
                'voltage_r' => $row['vol_r'] ?? null,
                'voltage_s' => $row['vol_s'] ?? null,
                'voltage_t' => $row['vol_t'] ?? null,
                'current_r' => $row['cur_r'] ?? null,
                'current_s' => $row['cur_s'] ?? null,
                'current_t' => $row['cur_t'] ?? null,
                'pf' => $row['pf'] ?? null,
                'energy_import' => $row['import_active_energy_send'] ?? null,
                'energy_export' => $row['export_active_energy_receive'] ?? null,
                'reactive_import' => $row['import_total_reactive_energy'] ?? null,
                'reactive_export' => $row['export_total_reactive_energy'] ?? null,
                'current_netral' => $row['current_netral'] ?? null,
                'apparent_power' => $row['apparent_power_send'] ?? null,
            ];
        }

        if (!empty($batch)) {
            // Dispatch per chunk 1000 row
            ProcessAMIAnalyzerJob::dispatch($batch);
        }
    }

    public function chunkSize(): int
    {
        return 5000; // batch 5000 row
    }
}
