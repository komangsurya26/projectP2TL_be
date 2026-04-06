<?php

namespace App\Http\Controllers;

class TemplateController extends Controller
{
    public function download($type)
    {
        $types = match ($type) {
            'dil' => 'dil',
            'ami' => 'ami',
            'amr' => 'amr',
            'epm' => 'epm',
            'prabayar' => 'prabayar',
            'sorek' => 'sorek',
            default => null,
        };

        if ($types === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Type tidak valid',
            ], 422);
        }

        $filename = strtoupper($types) . '_Template.csv';
        $headers = config('csv_headers.' . $types);

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
