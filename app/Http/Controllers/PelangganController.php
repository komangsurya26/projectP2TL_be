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

        // Filter berdasarkan jenis_meter
        if ($request->has('jenis_meter') && $request->query('jenis_meter') !== null) {
            $jenis_meter = $request->query('jenis_meter');
            $query->where('jenis_meter', strtoupper($jenis_meter));
        }

        // Filter berdasarkan idpel
        if ($request->has('idpel') && $request->query('idpel') !== null) {
            $query->where('idpel', $request->query('idpel'));
        }

        // Filter status
        if ($request->has('status') && $request->query('status') !== null) {
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
                    'address' => $pelanggan->nama_up,
                    "phone" => $pelanggan->notelp,
                    'meterType' => strtolower($pelanggan->jenis_meter),
                    'meterNumber' => $pelanggan->nometer,
                    'result' => $pelanggan->analisaResult ? ucfirst(strtolower($pelanggan->analisaResult->status)) : 'Unknown',
                    'risk' => $pelanggan->analisaResult ? (function ($status) {
                        if ($status === 'NORMAL') {
                            return 'low';
                        } elseif ($status === 'SUSPECT') {
                            return 'medium';
                        } elseif ($status === 'ANOMALY') {
                            return 'high';
                        }
                    })($pelanggan->analisaResult->status) : 'unknown',
                ];
            }), // ambil data saja
        ], 200);
    }
}
