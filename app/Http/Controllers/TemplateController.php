<?php

namespace App\Http\Controllers;

class TemplateController extends Controller
{
    public function downloadDil()
    {
        $filename = 'DIL_Template.csv';
        $headers = config('csv_headers.dil');

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function downloadAmi()
    {
        $filename = 'AMI_Template.csv';
        $headers = config('csv_headers.ami');

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
