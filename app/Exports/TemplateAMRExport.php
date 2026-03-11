<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TemplateAMRExport implements FromCollection, WithHeadings
{
    public function headings(): array
    {
        return [

        ];
    }

    public function collection()
    {
        return new Collection([

            [
            
            ]
        ]);
    }
}
