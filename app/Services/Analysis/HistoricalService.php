<?php

namespace App\Services\Analysis;

use App\Models\MeterAnalysis;
use Carbon\Carbon;

class HistoricalService
{
    public function getAverage($meterId, $date, $days)
    {
        return MeterAnalysis::where('meter_id', $meterId)
            ->whereBetween('analysis_date', [
                Carbon::parse($date)->subDays($days),
                Carbon::parse($date)->subDay()
            ])
            ->avg('consumption_kwh');
    }

    public function getZeroDays($meterId, $date, $days = 3)
    {
        return MeterAnalysis::where('meter_id', $meterId)
            ->whereBetween('analysis_date', [
                now()->parse($date)->subDays($days),
                now()->parse($date)->subDay()
            ])
            ->where('consumption_kwh', 0)
            ->count();
    }
}
