<?php

namespace App\Http\Controllers;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TemplateDILExport;
use App\Exports\TemplateAMIExport;
use App\Exports\TemplateAMRExport;

class TemplateController extends Controller
{
    public function download_dil()
    {
        return Excel::download(
            new TemplateDILExport,
            'Template_DIL.xlsx'
        );
    }

    public function download_ami()
    {
        return Excel::download(
            new TemplateAMIExport,
            'Template_TO_AMI.xlsx'
        );
    }

    public function download_amr()
    {
        return Excel::download(
            new TemplateAMRExport,
            'Template_TO_AMR.xlsx'
        );
    }
}
