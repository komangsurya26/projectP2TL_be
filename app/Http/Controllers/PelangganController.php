<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pelanggan;

class PelangganController extends Controller
{
    public function get(Request $request)
    {
        $idpelOrMeter = $request->input('idpel');

        // Base query dengan join agar pencarian idpel atau meter_number cepat
        $query = Pelanggan::query()
            ->leftJoin('meters', 'pelanggans.idpel', '=', 'meters.idpel')
            ->leftJoin('meter_analysis', 'meters.id', '=', 'meter_analysis.meter_id')
            ->select('pelanggans.*')
            ->distinct();

        // Filter idpel atau nomor meter
        if ($idpelOrMeter) {
            $query->where(function ($q) use ($idpelOrMeter) {
                $q->where('pelanggans.idpel', $idpelOrMeter)
                    ->orWhere('meters.meter_number', $idpelOrMeter);
            });
        }

        // Filter jenis meter
        if ($request->filled('jenis_meter')) {
            $jenis = strtoupper($request->jenis_meter);
            $query->where('meters.meter_type', $jenis);
        }

        // Filter status anomaly
        if ($request->filled('status')) {
            $status = $request->status;
            $query->where('meter_analysis.anomaly_status', $status);
        }

        // Pagination
        $perPage = $request->query('per_page', 50);
        $pelanggan = $query->paginate($perPage);

        // Ambil meters dan analysis via eager loading untuk mapping
        $pelanggan->load(['meters.analysis']);

        $data = $pelanggan->map(function ($pelanggan) {
            $meter = $pelanggan->meters->first();
            $analysis = $meter?->analysis?->first();

            return [
                'id' => $pelanggan->idpel,
                'name' => $pelanggan->nama,
                'tariff' => $meter ? $meter->tariff : null,
                'power' => $meter ? $meter->power_capacity . ' VA' : null,
                'address' => $pelanggan->alamat,
                'phone' => $pelanggan->notelp,
                'meterType' => $meter ? strtolower($meter->meter_type) : null,
                'meterNumber' => $meter ? $meter->meter_number : null,
                'result' => $analysis?->anomaly_status ?? 'UNKNOWN',
                'risk_score' => $analysis?->anomaly_score ?? '-',
            ];
        });

        return response()->json([
            'status' => 'success',
            'meta' => [
                'current_page' => $pelanggan->currentPage(),
                'per_page' => $pelanggan->perPage(),
                'total' => $pelanggan->total(),
                'last_page' => $pelanggan->lastPage(),
            ],
            'data' => $data,
        ]);
    }
}
