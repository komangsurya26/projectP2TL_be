<?php

namespace App\Helpers;

class CsvHelper
{
    public static function detectDelimiter($filePath)
    {
        $delimiters = ["\t", ";", ","];
        $counts = [];

        $handle = fopen($filePath, "r");
        $firstLine = fgets($handle);
        fclose($handle);

        foreach ($delimiters as $delimiter) {
            $counts[$delimiter] = substr_count($firstLine, $delimiter);
        }

        return array_search(max($counts), $counts);
    }

    public static function normalizeRow($row, $headers)
    {
        $row = array_pad($row, count($headers), null);
        $row = array_slice($row, 0, count($headers));
        return array_combine($headers, $row);
    }
}
