<?php

namespace App\Helpers;

class DateHelper
{
    public static function parse($value): ?string
    {
        if (empty($value)) return null;

        try {
            return date('Y-m-d H:i:s', strtotime($value));
        } catch (\Exception $e) {
            return null;
        }
    }
}
