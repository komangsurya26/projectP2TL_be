<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AMIImport;

class AMIImportController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        $filePath = $request->file('file')->store('ami_uploads');

        // Dispatch import ke queue, langsung pakai chunk reading
        Excel::queueImport(new AMIImport, $filePath);

        return response()->json([
            'status' => 'success',
            'message' => 'File diterima, proses import & analisa dijalankan di background'
        ]);
    }
}
