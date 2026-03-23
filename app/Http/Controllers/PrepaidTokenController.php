<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Meters;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PrepaidTokenController extends Controller
{
    private function formatToken($token)
    {
        $clean = preg_replace('/\D/', '', $token);
        return trim(chunk_split($clean, 4, '-'), '-');
    }
    private function formatRupiah($amount)
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
    public function getPurchaseHistory($idPel)
    {
        $meter = Meters::where('idpel', $idPel)->first();

        if (!$meter) {
            return response()->json([
                'status' => 'error',
                'message' => 'Meter not found'
            ], 404);
        }

        $tokens = $meter->prepaidTokens()
            ->orderBy('purchase_date', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $tokens->map(function ($item) {
                return [
                    'date' => $item->purchase_date,
                    'amount' => $this->formatRupiah($item->amount_paid),
                    'energy' => $item->kwh_purchased . ' kWh',
                    'token' => $this->formatToken($item->token_number),
                ];
            })
        ]);
    }

    public function getMonthlyUsage($idPel)
    {
        $meter = Meters::where('idpel', $idPel)->first();

        if (!$meter) {
            return response()->json([
                'status' => 'error',
                'message' => 'Meter not found'
            ], 404);
        }

        // Ambil usage 12 bulan terakhir
        $usages = DB::table('prepaid_tokens')
            ->selectRaw("
        DATE_TRUNC('month', purchase_date) as month,
        SUM(kwh_purchased) as usage
    ")
            ->where('meter_id', $meter->id)
            ->where('purchase_date', '>=', now()->subMonths(12))
            ->groupByRaw("DATE_TRUNC('month', purchase_date)")
            ->orderByRaw("DATE_TRUNC('month', purchase_date)")
            ->get()
            ->map(function ($item) {
                return [
                    'month' => Carbon::parse($item->month)->format('Y-m'),
                    'usage' => $item->usage
                ];
            });

        // Generate 12 bulan terakhir (biar tidak bolong)
        $result = collect(range(0, 11))->map(function ($i) use ($usages) {
            $date = now()->subMonths(11 - $i);
            $key = $date->format('Y-m');

            return [
                'month' => $key,
                'label' => $date->format('M Y'),
                'usage' => $usages[$key]->usage ?? 0,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $result
        ]);
    }

    public function getTokenTrend($idPel)
    {
        $meter = Meters::where('idpel', $idPel)->first();

        if (!$meter) {
            return response()->json([
                'status' => 'error',
                'message' => 'Meter not found'
            ], 404);
        }

        $raw = DB::table('prepaid_tokens')
            ->selectRaw("
            DATE_TRUNC('month', purchase_date) as month,
            SUM(kwh_purchased) as energy,
            COUNT(*) as frequency
        ")
            ->where('meter_id', $meter->id)
            ->where('purchase_date', '>=', now()->subMonths(12))
            ->groupByRaw("DATE_TRUNC('month', purchase_date)")
            ->orderByRaw("DATE_TRUNC('month', purchase_date)")
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->month)->format('Y-m');
            });

        // generate 12 bulan (biar tidak bolong)
        $result = collect(range(0, 11))->map(function ($i) use ($raw) {
            $date = now()->subMonths(11 - $i);
            $key = $date->format('Y-m');

            return [
                'month' => $date->format('M'), // Jan, Feb
                'label' => $date->format('M Y'),
                'energy' => $raw[$key]->energy ?? 0,
                'frequency' => $raw[$key]->frequency ?? 0,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $result
        ]);
    }
}
