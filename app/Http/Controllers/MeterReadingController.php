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

    public function voltageTrend($meterNumber)
    {
        return $meterNumber;
        $meter = Meters::where('meter_number', $meterNumber)->first();

        if (!$meter) {
            return response()->json([
                'status' => 'error',
                'message' => 'Meter not found'
            ], 404);
        }

        $readings = DB::table('meter_readings')
            ->where('meter_id', $meter->id)
            ->orderBy('reading_time')
            ->get();

        // Buat interval 4 jam
        $intervals = [
            '00:00' => [],
            '04:00' => [],
            '08:00' => [],
            '12:00' => [],
            '16:00' => [],
            '20:00' => [],
        ];

        foreach ($readings as $r) {
            $hour = (int) date('H', strtotime($r->reading_time));

            if ($hour >= 0 && $hour < 4) {
                $intervals['00:00'][] = $r;
            } elseif ($hour >= 4 && $hour < 8) {
                $intervals['04:00'][] = $r;
            } elseif ($hour >= 8 && $hour < 12) {
                $intervals['08:00'][] = $r;
            } elseif ($hour >= 12 && $hour < 16) {
                $intervals['12:00'][] = $r;
            } elseif ($hour >= 16 && $hour < 20) {
                $intervals['16:00'][] = $r;
            } else {
                $intervals['20:00'][] = $r;
            }
        }

        // Hitung rata-rata voltage per interval
        $result = [];
        foreach ($intervals as $time => $group) {
            if (count($group) > 0) {
                $avgVoltage = collect($group)->avg(function ($r) {
                    // misal ambil rata-rata voltage_r,s,t
                    $voltages = array_filter([
                        $r->voltage_r,
                        $r->voltage_s,
                        $r->voltage_t,
                    ]);
                    return count($voltages) > 0 ? array_sum($voltages) / count($voltages) : null;
                });
                $result[] = [
                    'time' => $time,
                    'voltage' => round($avgVoltage, 2),
                ];
            } else {
                $result[] = [
                    'time' => $time,
                    'voltage' => 0,
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'meter_id' => $meter->id,
            'data' => $result
        ]);
    }
}
