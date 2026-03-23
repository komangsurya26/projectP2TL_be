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
        $file = $request->file('file');
        $chunkIndex = $request->input('chunkIndex');
        $totalChunks = $request->input('totalChunks');

        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) mkdir($tempDir, 0755, true);

        $tempPath = $tempDir . '/' . $file->getClientOriginalName() . ".part{$chunkIndex}";
        $file->move($tempDir, basename($tempPath));

        // cek apakah semua chunk sudah ada
        $allChunksExist = true;
        for ($i = 0; $i < $totalChunks; $i++) {
            if (!file_exists($tempDir . '/' . $file->getClientOriginalName() . ".part{$i}")) {
                $allChunksExist = false;
                break;
            }
        }

        if (!$allChunksExist) {
            // tunggu chunk lain
            return response()->json(['status' => 'pending', 'message' => 'Menunggu chunk lain']);
        }

        // gabungkan semua chunk
        $finalPath = storage_path('app/dil_uploads/' . $file->getClientOriginalName());
        $out = fopen($finalPath, 'wb');
        for ($i = 0; $i < $totalChunks; $i++) {
            $in = fopen($tempDir . '/' . $file->getClientOriginalName() . ".part{$i}", 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
            unlink($tempDir . '/' . $file->getClientOriginalName() . ".part{$i}");
        }
        fclose($out);

        // dispatch background job
        ProcessDILImportJob::dispatch($finalPath);

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
