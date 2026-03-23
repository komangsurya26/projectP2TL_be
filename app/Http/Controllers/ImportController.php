<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessAMIImportJob;
use Illuminate\Http\Request;
use App\Jobs\ProcessDILImportJob;
use App\Models\UploadHistory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class ImportController extends Controller
{
    public function uploadDil(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt',
            'chunkIndex' => 'required|integer|min:0',
            'totalChunks' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi csv gagal',
            ], 422);
        }

        $file = $request->file('file');
        $chunkIndex = $request->input('chunkIndex');
        $totalChunks = $request->input('totalChunks');

        $tempDir = 'temp';
        $tempFile = $tempDir . '/' . $file->getClientOriginalName() . ".part{$chunkIndex}";
        Storage::disk('local')->putFileAs($tempDir, $file, basename($tempFile));

        $allChunksExist = true;
        for ($i = 0; $i < $totalChunks; $i++) {
            if (!Storage::disk('local')->exists($tempDir . '/' . $file->getClientOriginalName() . ".part{$i}")) {
                $allChunksExist = false;
                break;
            }
        }

        if (!$allChunksExist) {
            return response()->json([
                'status' => 'pending',
                'message' => 'Menunggu chunk lain'
            ]);
        }

        $finalPath = 'dil_uploads/' . $file->getClientOriginalName();
        $out = fopen(Storage::disk('local')->path($finalPath), 'wb');

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = Storage::disk('local')->path($tempDir . '/' . $file->getClientOriginalName() . ".part{$i}");
            $in = fopen($chunkPath, 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);

            Storage::disk('local')->delete($tempDir . '/' . $file->getClientOriginalName() . ".part{$i}");
        }
        fclose($out);

        $history = UploadHistory::create([
            'filename' => $file->getClientOriginalName(),
            'status' => 'pending',
            'rows' => 0,
        ]);

        $handle = fopen(Storage::disk('local')->path($finalPath), 'r');
        $headers = fgetcsv($handle, 0, ',');
        fclose($handle);

        $expectedHeaders = config('csv_headers.dil');
        if ($headers !== $expectedHeaders) {
            Storage::disk('local')->delete($finalPath);
            $history->update([
                'status' => 'error',
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Header CSV tidak sesuai'
            ], 422);
        }

        ProcessDILImportJob::dispatch(Storage::disk('local')->path($finalPath), $history->id);

        return response()->json([
            'status' => 'success',
            'message' => 'CSV diterima, proses import berjalan di background'
        ]);
    }

    public function uploadAmi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt',
            'chunkIndex' => 'required|integer|min:0',
            'totalChunks' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi csv gagal',
            ], 422);
        }

        $file = $request->file('file');
        $chunkIndex = $request->input('chunkIndex');
        $totalChunks = $request->input('totalChunks');

        $tempDir = 'temp';
        $tempFile = $tempDir . '/' . $file->getClientOriginalName() . ".part{$chunkIndex}";
        Storage::disk('local')->putFileAs($tempDir, $file, basename($tempFile));

        $allChunksExist = true;
        for ($i = 0; $i < $totalChunks; $i++) {
            if (!Storage::disk('local')->exists($tempDir . '/' . $file->getClientOriginalName() . ".part{$i}")) {
                $allChunksExist = false;
                break;
            }
        }

        if (!$allChunksExist) {
            return response()->json([
                'status' => 'pending',
                'message' => 'Menunggu chunk lain'
            ]);
        }

        $finalPath = 'ami_uploads/' . $file->getClientOriginalName();
        $out = fopen(Storage::disk('local')->path($finalPath), 'wb');

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = Storage::disk('local')->path($tempDir . '/' . $file->getClientOriginalName() . ".part{$i}");
            $in = fopen($chunkPath, 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);

            Storage::disk('local')->delete($tempDir . '/' . $file->getClientOriginalName() . ".part{$i}");
        }
        fclose($out);

        $handle = fopen(Storage::disk('local')->path($finalPath), 'r');
        $headers = fgetcsv($handle, 0, ',');
        fclose($handle);

        $history = UploadHistory::create([
            'filename' => $file->getClientOriginalName(),
            'status' => 'pending',
            'rows' => 0,
        ]);

        $expectedHeaders = config('csv_headers.ami');
        if ($headers !== $expectedHeaders) {
            Storage::disk('local')->delete($finalPath);
            $history->update([
                'status' => 'error',
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Header CSV tidak sesuai dengan yang diharapkan'
            ], 422);
        }

        ProcessAMIImportJob::dispatch(Storage::disk('local')->path($finalPath), $history->id);

        return response()->json([
            'status' => 'success',
            'message' => 'CSV diterima, proses import berjalan di background'
        ], 200);
    }
}
