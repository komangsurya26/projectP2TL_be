<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Jobs\ProcessDILImportJob;
use Maatwebsite\Excel\Concerns\ToArray;

class DILImport implements ToArray, WithHeadingRow, WithChunkReading, ShouldQueue
{
    public function array(array $rows)
    {
        ProcessDILImportJob::dispatch($rows);
    }

    public function chunkSize(): int
    {
        return 5000; // proses per 1000 row
    }
}
