<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Jobs\ProcessDILImportJob;

class DILImport implements ToCollection, WithHeadingRow, WithChunkReading, ShouldQueue
{
    public function collection(Collection $rows)
    {
        $batch = [];

        foreach ($rows as $row) {
            $batch[] = $row->toArray();
        }

        if (!empty($batch)) {
            ProcessDILImportJob::dispatch($batch);
        }
    }

    public function chunkSize(): int
    {
        return 5000; // proses per 5000 row
    }
}
