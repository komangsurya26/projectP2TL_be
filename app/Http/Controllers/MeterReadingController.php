<?php

namespace App\Http\Controllers;

use App\Models\Meters;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MeterReadingController extends Controller
{
    public function getMonthlyUsageAMI($meterNumber)
    {
        $meter = Meters::where('meter_number', $meterNumber)->first();

        if (!$meter) {
            return response()->json([
                'status' => 'error',
                'message' => 'Meter not found'
            ], 404);
        }

        $raw = DB::table('meter_readings')
            ->selectRaw("
            DATE_TRUNC('month', reading_time) as month,
            MAX(import_kwh) - MIN(import_kwh) as usage
        ")
            ->where('meter_id', $meter->id)
            ->where('reading_time', '>=', now()->subMonths(12))
            ->groupByRaw("DATE_TRUNC('month', reading_time)")
            ->orderByRaw("DATE_TRUNC('month', reading_time)")
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->month)->format('Y-m');
            });

        // Fill missing months (biar chart tidak bolong)
        $result = collect(range(0, 11))->map(function ($i) use ($raw) {
            $date = now()->subMonths(11 - $i);
            $key = $date->format('Y-m');

            return [
                'month' => $date->format('M'),
                'label' => $date->format('M Y'),
                'usage' => round($raw[$key]->usage ?? 0, 2),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $result
        ]);
    }

    public function yearlyUsageAMI($meterNumber)
    {
        $meter = Meters::where('meter_number', $meterNumber)->first();

        if (!$meter) {
            return response()->json([
                'status' => 'error',
                'message' => 'Meter not found'
            ], 404);
        }

        $monthly = DB::table('meter_readings')
            ->selectRaw("DATE_TRUNC('month', reading_time) AS month,
                     EXTRACT(YEAR FROM reading_time) AS year,
                     MAX(import_kwh) AS max_kwh,
                     MIN(import_kwh) AS min_kwh")
            ->where('meter_id', $meter->id)
            ->groupBy('month', 'year')
            ->orderBy('month')
            ->get();

        $yearly = $monthly->groupBy('year')->map(function ($group, $year) {
            return [
                'year' => (int) $year,
                'total_usage' => $group->sum(fn($m) => $m->max_kwh - $m->min_kwh),
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'data' => $yearly
        ]);
    }
}
