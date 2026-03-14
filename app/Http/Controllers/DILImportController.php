<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DILImport;
use App\Jobs\ProcessDILImportStagingJob;

class DILImportController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        $filePath = $request->file('file')->store('dil_uploads');

        Excel::queueImport(new DILImport, $filePath);

        return response()->json([
            'status' => 'success',
            'message' => 'File diterima, proses import dijalankan di background'
        ]);
    }
}
