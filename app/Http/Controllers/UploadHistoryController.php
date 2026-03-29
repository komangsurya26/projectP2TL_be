<?php

namespace App\Http\Controllers;

use App\Models\UploadHistory;

class UploadHistoryController extends Controller
{
    public function get()
    {
        $histories = UploadHistory::with('user:id,name')->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'filename' => $item->filename,
                    'date' => $item->created_at->format('Y-m-d'),
                    'status' => $item->status,
                    'rows' => $item->rows,
                    'uploaded_by' => $item->user->name ?? '-',
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $histories,
        ]);
    }
}
