<?php

namespace App\Helpers;

class NumberHelper
{
    public static function safeFloat($value)
    {
        if ($value === null || $value === '') return null;

        $value = trim($value);

        if (strpos($value, ',') !== false && strpos($value, '.') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
