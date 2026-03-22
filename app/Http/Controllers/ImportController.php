<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessAMIImportJob;
use Illuminate\Http\Request;
use App\Jobs\ProcessDILImportJob;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    public function uploadDil(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $path = $request->file('file')->store('dil_uploads');

        ProcessDILImportJob::dispatch(Storage::path($path));

        return response()->json([
            'status' => 'success',
            'message' => 'CSV diterima, proses import berjalan di background'
        ]);
    }

    public function uploadAmi(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $path = $request->file('file')->store('ami_uploads');

        ProcessAMIImportJob::dispatch(Storage::path($path));

        return response()->json([
            'status' => 'success',
            'message' => 'CSV diterima, proses import berjalan di background'
        ]);
    }
}
