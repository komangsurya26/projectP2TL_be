<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Meters;

class PrepaidTokenController extends Controller
{
    private function formatToken($token)
    {
        $clean = preg_replace('/\D/', '', $token);
        return trim(chunk_split($clean, 4, '-'), '-');
    }
    public function getPurchaseHistory($meterNumber)
    {
        $meter = Meters::where('meter_number', $meterNumber)->first();

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
                    'amount' => 'Rp. ' . $item->amount_paid,
                    'energy' => $item->kwh_purchased . ' kWh',
                    'token' => $this->formatToken($item->token_number),
                ];
            })
        ]);
    }
}
