<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Pelanggan;

class DILImportController extends Controller
{
    public function upload(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls'
            ]);

            $rows = Excel::toArray([], $request->file('file'));

            $data = $rows[0];

            $header = array_map('strtoupper', $data[0]);
            unset($data[0]);

            foreach ($data as $row) {

                $row = array_combine($header, $row);

                $meterType = null;

                if (isset($row['KDPEMBMETER']) && strtoupper($row['KDPEMBMETER']) === 'P') {
                    $meterType = 'PRABAYAR';
                } elseif (isset($row['KDPEMBMETER']) && strtoupper($row['KDPEMBMETER']) === 'L') {
                    $meterType = 'AMI';
                } elseif (isset($row['KDPEMBMETER']) && strtoupper($row['KDPEMBMETER']) === 'E') {
                    $meterType = 'PASKABAYAR';
                } else {
                    $meterType = 'AMR';
                }

                Pelanggan::updateOrCreate(
                    [
                        'idpel' => $row['IDPEL']
                    ],
                    [
                        'nama' => $row['NAMA'] ?? null,
                        'tarif' => $row['TARIF'] ?? null,
                        'daya' => $row['DAYA'] ?? null,
                        'nometer' => $row['NOMOR_METER_KWH'] ?? null,

                        'kelurahan' => $row['NAMA_KELURAHAN'] ?? null,
                        'kecamatan' => $row['NAMA_KECAMATAN'] ?? null,
                        'kabupaten' => $row['NAMA_KABUPATEN'] ?? null,

                        'unitup' => $row['UNITUP'] ?? null,

                        'koordinat_x' => $row['KOORDINAT_X'] ?? null,
                        'koordinat_y' => $row['KOORDINAT_Y'] ?? null,

                        'notelp' => $row['NOTELP_HP'] ?? null,

                        'jenis_meter' => $meterType
                    ]
                );
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data pelanggan berhasil diimport'
            ]);
        } catch (\Throwable $th) {
            // dd($th);
        }
    }
}
