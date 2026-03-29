<?php

namespace App\Helpers;

class CoordinateHelper
{
    public static function normalizeLatitude(?string $value): ?float
    {
        if ($value === null || $value === '') return null;

        // Hapus karakter selain angka & minus
        $value = preg_replace('/[^0-9\-]/', '', $value);
        $isNegative = strpos($value, '-') === 0;
        $value = str_replace('-', '', $value);

        $beforeDot = substr($value, 0, 1);    // 1 digit sebelum titik
        $afterDot  = substr($value, 1, 6);    // 6 digit desimal
        $afterDot = str_pad($afterDot, 6, '0');

        return $isNegative ? -(float)($beforeDot . '.' . $afterDot) : (float)($beforeDot . '.' . $afterDot);
    }

    public static function normalizeLongitude(?string $value): ?float
    {
        if ($value === null || $value === '') return null;

        $value = preg_replace('/[^0-9\-]/', '', $value);
        $isNegative = strpos($value, '-') === 0;
        $value = str_replace('-', '', $value);

        $beforeDot = substr($value, 0, 3);    // 3 digit sebelum titik
        $afterDot  = substr($value, 3, 6);    // 6 digit desimal
        $afterDot = str_pad($afterDot, 6, '0');

        return $isNegative ? -(float)($beforeDot . '.' . $afterDot) : (float)($beforeDot . '.' . $afterDot);
    }
}
