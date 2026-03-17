<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pelanggan;

class PelangganController extends Controller
{
    // note: query default laravel tanpa perlu diisi di codingan yaitu = ?page=1/2/3 dst
    public function get(Request $request)
    {
        $query = Pelanggan::with(['meters']);

        // Filter idpel atau nomor meter
        if ($request->filled('idpel')) {
            $idpel = $request->idpel;
            $query->where('idpel', $idpel)
                ->orWhereHas('meters', function ($q) use ($idpel) {
                    $q->where('meter_number', $idpel);
                });
        }

        // Filter jenis meter
        if ($request->filled('jenis_meter')) {
            $query->whereHas('meters', function ($q) use ($request) {
                $q->where('meter_type', strtoupper($request->jenis_meter));
            });
        }

        $perPage = $request->query('per_page', 50);

        $pelanggan = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'meta' => [
                'current_page' => $pelanggan->currentPage(),
                'per_page' => $pelanggan->perPage(),
                'total' => $pelanggan->total(),
                'last_page' => $pelanggan->lastPage(),
            ],
            'data' => $pelanggan->map(function ($pelanggan) {
                $meter = $pelanggan->meters->first();
                $analysis = $meter?->analysis?->first();
                $risk_score = $analysis?->anomaly_score ?? '-';
                $status = $analysis?->anomaly_status;

                return [
                    'id' => $pelanggan->idpel,
                    'name' => $pelanggan->nama,
                    'tariff' => $meter ? $meter->tariff : null,
                    'power' => $meter ? $meter->power_capacity . ' VA' : null,
                    'address' => $pelanggan->alamat,
                    'phone' => $pelanggan->notelp,
                    'meterType' => $meter ? strtolower($meter->meter_type) : null,
                    'meterNumber' => $meter ? $meter->meter_number : null,
                    'result' => $status ?? 'Unknown',
                    'risk_score' => $risk_score,
                ];
            }),
        ]);
    }
}
