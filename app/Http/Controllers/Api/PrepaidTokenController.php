<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrepaidToken;

class PrepaidTokenController extends Controller
{
    public function getPurchaseHistory($meterId)
    {
        $tokens = PrepaidToken::where('meter_id', $meterId)
            ->orderBy('purchase_date', 'desc')
            ->paginate(10);

        if (!$tokens->count()) {
            return response()->json([
                'status' => 'success',
                'data' => []
            ]);
        }

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
