<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessAMIImportJob;
use Illuminate\Http\Request;
use App\Jobs\ProcessDILImportJob;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class ImportController extends Controller
{
    public function uploadDil(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi csv gagal',
            ], 422);
        }

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak bisa membuka file CSV'
            ], 422);
        }

        $headers = fgetcsv($handle, 0, ',');
        fclose($handle);

        $expectedHeaders = config('csv_headers.dil');

        if ($headers !== $expectedHeaders) {
            return response()->json([
                'status' => 'error',
                'message' => 'Header CSV tidak sesuai dengan yang diharapkan'
            ], 422);
        }

        $path = $file->store('dil_uploads');
        ProcessDILImportJob::dispatch(Storage::path($path));

        return response()->json([
            'status' => 'success',
            'message' => 'CSV diterima, proses import berjalan di background'
        ]);
    }

    public function uploadAmi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi csv gagal',
            ], 422);
        }

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak bisa membuka file CSV'
            ], 422);
        }

        $headers = fgetcsv($handle, 0, ',');
        fclose($handle);

        $expectedHeaders = config('csv_headers.ami');

        if ($headers !== $expectedHeaders) {
            return response()->json([
                'status' => 'error',
                'message' => 'Header CSV tidak sesuai dengan yang diharapkan'
            ], 422);
        }

        $path = $file->store('ami_uploads');
        ProcessAMIImportJob::dispatch(Storage::path($path));

        return response()->json([
            'status' => 'success',
            'message' => 'CSV diterima, proses import berjalan di background'
        ], 200);
    }
}
