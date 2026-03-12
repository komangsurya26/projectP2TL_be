<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pelanggan;

class PelangganController extends Controller
{
    // note: query default laravel tanpa perlu diisi di codingan yaitu = ?page=1/2/3 dst
    public function get(Request $request)
    {
        $query = Pelanggan::with(['measurements', 'analisaResults']);

        // Filter berdasarkan idpel
        if ($request->has('idpel')) {
            $query->where('idpel', $request->query('idpel'));
        }

        // Filter status
        if ($request->has('status')) {
            $status = strtoupper($request->query('status'));
            $query->whereHas('analisaResults', function ($q) use ($status) {
                if ($status === 'NORMAL') {
                    $q->where('status', 'NORMAL');
                } elseif ($status === 'SUSPECT') {
                    $q->where('status', 'SUSPECT');
                } elseif ($status === 'ANOMALY') {
                    $q->where('status', 'ANOMALY');
                }
            });
        }

        // Mengecek apakah ingin paginate atau ambil semua
        $perPage = $request->query('per_page', 50); // default 50 data per halaman

        $pelanggan = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $pelanggan
        ], 200);
    }
}
