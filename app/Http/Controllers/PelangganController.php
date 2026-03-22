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
            ->leftJoin('meter_analysis as ma', function ($join) {
                $join->on('meters.id', '=', 'ma.meter_id')
                    ->whereRaw('ma.analysis_date = (select max(ma2.analysis_date) from meter_analysis ma2 where ma2.meter_id = meters.id)');
            })
            ->select(
                'pelanggans.*',
                'meters.meter_number',
                'meters.meter_type',
                'meters.tariff',
                'meters.power_capacity',
                'ma.anomaly_status',
                'ma.anomaly_score'
            );

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
            $query->where('ma.anomaly_status', $status);
        }

        // Pagination
        $perPage = $request->query('per_page', 50);
        $pelanggan = $query->paginate($perPage);

        $data = $pelanggan->map(function ($pelanggan) {
            return [
                'id' => $pelanggan->idpel,
                'name' => $pelanggan->nama,
                'tariff' => $pelanggan->tariff,
                'power' => $pelanggan->power_capacity . ' VA',
                'address' => $pelanggan->alamat,
                'phone' => $pelanggan->notelp,
                'meterType' => strtolower($pelanggan->meter_type),
                'meterNumber' => $pelanggan->meter_number,
                'result' => $pelanggan->anomaly_status ?? 'UNKNOWN',
                'risk_score' => $pelanggan->anomaly_score ?? '-',
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
