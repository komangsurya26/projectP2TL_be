<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ProcessDILImportJob;
use Illuminate\Support\Facades\Storage;

class DILImportController extends Controller
{
    public function upload(Request $request)
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
}
