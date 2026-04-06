<?php

namespace App\Http\Controllers;

use App\Helpers\CsvHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\UploadHistory;
use App\Jobs\ProcessDILImportJob;
use App\Jobs\ProcessAMIImportJob;
use App\Jobs\ProcessAMRImportJob;
use App\Jobs\ProcessEPMImportJob;
use App\Jobs\ProcessPrabayarImportJob;
use App\Jobs\ProcessSorekImportJob;
use Illuminate\Support\Facades\Auth;

class ImportController extends Controller
{
    public function upload(Request $request, $type)
    {
        $userId = Auth::user()->id;
        $jobClass = match ($type) {
            'dil' => ProcessDILImportJob::class,
            'ami' => ProcessAMIImportJob::class,
            'amr' => ProcessAMRImportJob::class,
            'epm' => ProcessEPMImportJob::class,
            'prabayar' => ProcessPrabayarImportJob::class,
            'sorek' => ProcessSorekImportJob::class,
            default => null,
        };

        if ($jobClass === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Type tidak valid',
            ], 422);
        }

        return $this->handleChunkUpload(
            $request,
            $type . '_uploads',
            'csv_headers.' . $type,
            $jobClass,
            $userId
        );
    }

    /**
     * Generic handler untuk semua upload CSV chunk
     */
    private function handleChunkUpload(
        Request $request,
        string $uploadDir,
        string $headerConfig,
        string $jobClass,
        string $userId
    ) {
        // Validation
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
        $chunkIndex = (int) $request->input('chunkIndex');
        $totalChunks = (int) $request->input('totalChunks');

        $tempDir = 'temp';
        $originalName = $file->getClientOriginalName();

        // Hindari tabrakan nama file
        $fileHash = md5($originalName . $totalChunks);
        $fileName = "{$fileHash}_{$originalName}";

        // Simpan chunk
        $chunkPath = "{$tempDir}/{$fileName}.part{$chunkIndex}";
        Storage::disk('local')->putFileAs($tempDir, $file, basename($chunkPath));

        // Cek apakah semua chunk sudah ada
        for ($i = 0; $i < $totalChunks; $i++) {
            if (!Storage::disk('local')->exists("{$tempDir}/{$fileName}.part{$i}")) {
                return response()->json([
                    'status' => 'pending',
                    'message' => 'Menunggu chunk lain'
                ]);
            }
        }

        // Gabungkan file
        $finalPath = "{$uploadDir}/{$fileName}";
        $fullFinalPath = Storage::disk('local')->path($finalPath);

        // pastikan folder ada
        if (!Storage::disk('local')->exists($uploadDir)) {
            Storage::disk('local')->makeDirectory($uploadDir);
        }

        $out = fopen($fullFinalPath, 'wb');

        for ($i = 0; $i < $totalChunks; $i++) {
            $partPath = Storage::disk('local')->path("{$tempDir}/{$fileName}.part{$i}");
            $in = fopen($partPath, 'rb');

            stream_copy_to_stream($in, $out);

            fclose($in);
            Storage::disk('local')->delete("{$tempDir}/{$fileName}.part{$i}");
        }

        fclose($out);

        // Simpan history
        $history = UploadHistory::create([
            'filename' => $originalName,
            'status' => 'pending',
            'rows' => 0,
            'uploaded_by' => $userId,
        ]);

        // Validasi header CSV
        $handle = fopen($fullFinalPath, 'r');
        $delimiter = CsvHelper::detectDelimiter($fullFinalPath);
        $headers = fgetcsv($handle, 0, $delimiter);
        fclose($handle);

        // remove BOM di header
        $headers = array_map(function ($h) {
            return preg_replace('/^\x{FEFF}/u', '', $h);
        }, $headers);

        $expectedHeaders = config($headerConfig);

        if ($headers !== $expectedHeaders) {
            Storage::disk('local')->delete($finalPath);

            $history->update([
                'status' => 'error',
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Header CSV tidak sesuai',
                'headers' => $headers,
                'expectedHeaders' => $expectedHeaders
            ], 422);
        }

        // Dispatch job (dynamic)
        $jobClass::dispatch($fullFinalPath, $history->id);

        return response()->json([
            'status' => 'success',
            'message' => 'CSV diterima, proses import berjalan di background'
        ]);
    }
}
