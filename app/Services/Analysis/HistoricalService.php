<?php

namespace App\Services\Analysis;

use App\Models\MeterAnalysis;

class HistoricalService
{
    public function getAverage($date, $days, $alias = 'avg')
    {
        return MeterAnalysis::selectRaw("meter_id, AVG(consumption_kwh) as {$alias}")
            ->whereBetween('analysis_date', [
                now()->parse($date)->subDays($days),
                now()->parse($date)->subDay()
            ])
            ->groupBy('meter_id')
            ->pluck($alias, 'meter_id');
    }

    public function getZeroDays($date, $days = 3)
    {
        return MeterAnalysis::selectRaw('meter_id, COUNT(*) as zero_days')
            ->whereBetween('analysis_date', [
                now()->parse($date)->subDays($days),
                now()->parse($date)->subDay()
            ])
            ->where('consumption_kwh', 0)
            ->groupBy('meter_id')
            ->pluck('zero_days', 'meter_id');
    }
}
