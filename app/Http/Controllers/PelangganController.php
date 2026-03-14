<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pelanggan;

class PelangganController extends Controller
{
    // note: query default laravel tanpa perlu diisi di codingan yaitu = ?page=1/2/3 dst
    public function get(Request $request)
    {
        $query = Pelanggan::with(['measurements', 'analisaResult']);

        // Filter idpel
        if ($request->filled('idpel')) {
            $query->where('idpel', $request->idpel);
        }

        // Filter status
        if ($request->filled('status')) {
            $status = strtoupper($request->status);

            $query->whereHas('analisaResult', function ($q) use ($status) {
                $q->where('status', $status);
            });
        }

        // Filter jenis meter
        if ($request->filled('jenis_meter')) {
            $query->where('jenis_meter', strtoupper($request->jenis_meter));
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
                return [
                    'id' => $pelanggan->idpel,
                    'name' => $pelanggan->nama,
                    'tariff' => $pelanggan->tarif,
                    'power' => $pelanggan->daya . ' VA',
                    'address' => $pelanggan->alamat,
                    'phone' => $pelanggan->notelp,
                    'meterType' => strtolower($pelanggan->jenis_meter),
                    'meterNumber' => $pelanggan->nometer,
                    'result' => $pelanggan->analisaResult
                        ? ucfirst(strtolower($pelanggan->analisaResult->status))
                        : 'Unknown',
                    'risk' => $pelanggan->analisaResult
                        ? match ($pelanggan->analisaResult->status) {
                            'NORMAL' => 'low',
                            'SUSPECT' => 'medium',
                            'ANOMALY' => 'high',
                            default => 'unknown'
                        }
                        : 'unknown',
                ];
            }),
        ]);
    }
}
