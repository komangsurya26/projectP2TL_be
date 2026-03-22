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
}
