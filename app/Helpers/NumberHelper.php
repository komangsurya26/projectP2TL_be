<?php

namespace App\Helpers;

class NumberHelper
{
    public static function safeFloat($value)
    {
        if ($value === null || $value === '') return null;

        $value = trim($value);

        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? (float) $value : null;
    }
}
