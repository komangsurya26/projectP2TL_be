<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Meters;

class PrepaidTokenController extends Controller
{
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
                    'amount' => $item->amount_paid,
                    'energy' => $item->kwh_purchased,
                    'token' => $item->token_number,
                ];
            })
        ]);
    }
}
