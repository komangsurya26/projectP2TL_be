<?php

namespace App\Helpers;

class NumberHelper
{
    public static function safeFloat($value)
    {
        if ($value === null || $value === '') return null;

        $value = trim($value);

        // Hapus semua spasi
        $value = str_replace(' ', '', $value);

        // Kalau ada koma & titik → tentukan mana desimal
        if (strpos($value, ',') !== false && strpos($value, '.') !== false) {
            // Ambil posisi terakhir
            $lastComma = strrpos($value, ',');
            $lastDot   = strrpos($value, '.');

            if ($lastComma > $lastDot) {
                // Format Eropa: 21.493,75
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                // Format US: 21,493.75
                $value = str_replace(',', '', $value);
            }
        }
        // Kalau hanya koma
        elseif (strpos($value, ',') !== false) {
            // Bisa jadi desimal atau ribuan → cek jumlah koma
            if (substr_count($value, ',') > 1) {
                // 21,493,000 → ribuan
                $value = str_replace(',', '', $value);
            } else {
                // 21,5 → desimal
                $value = str_replace(',', '.', $value);
            }
        }
        // Kalau hanya titik
        elseif (strpos($value, '.') !== false) {
            if (substr_count($value, '.') > 1) {
                // 21.493.000 → ribuan
                $value = str_replace('.', '', $value);
            }
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
