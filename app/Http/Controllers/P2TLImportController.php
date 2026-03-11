<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\MlAnalysis;

class P2TLImportController extends Controller
{
    public function upload(Request $request)
    {
        // ini_set('max_execution_time', 300);

        // $request->validate([
        //     'file' => 'required|file'
        // ]);

        // $data = Excel::toArray([], $request->file('file'));

        // $rows = $data[0];

        // unset($rows[0]);

        // $result = [];

        // foreach ($rows as $row) {

        //     $idpel = $row[0];
        //     $nama = $row[1];
        //     $tarif = $row[2];
        //     $daya = $row[3];
        //     $unitup = $row[4];
        //     $gardu = $row[5];
        //     $no_tiang = $row[6];
        //     $koordinat_x = $row[7];
        //     $koordinat_y = $row[8];

        //     $bulan1 = $row[9];
        //     $bulan2 = $row[10];
        //     $bulan3 = $row[11];
        //     $bulan4 = $row[12];
        //     $bulan5 = $row[13];
        //     $bulan6 = $row[14];

        //     $avg = ($bulan1 + $bulan2 + $bulan3 + $bulan4 + $bulan5) / 5;

        //     $actual = $bulan6;

        //     $prompt = "

        // Anda adalah analis P2TL PLN.

        // Data pelanggan:

        // IDPEL:$idpel
        // Tarif:$tarif
        // Daya:$daya

        // Histori kWh:
        // $bulan1,$bulan2,$bulan3,$bulan4,$bulan5,$bulan6

        // Hitung:

        // expected_kwh
        // loss_kwh
        // risk_score
        // estimasi_rupiah

        // Output JSON

        // ";

        //     $response = Http::post(
        //         'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=AIzaSyBpYDNRDeUs3FDDjk-crw8ABqBcRiP5Shc',

        //         [
        //             "contents" => [
        //                 [
        //                     "parts" => [
        //                         ["text" => $prompt]
        //                     ]
        //                 ]
        //             ]
        //         ]
        //     );

        //     if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
        //         $text = $response['candidates'][0]['content']['parts'][0]['text'];


        //         // Ambil JSON saja
        //         preg_match('/```json(.*?)```/s', $text, $matches);
        //         $json = isset($matches[1]) ? json_decode(trim($matches[1]), true) : [];

        //         $customer = Customer::updateOrCreate(

        //             ['idpel' => $idpel],

        //             [
        //                 'nama' => $nama,
        //                 'tarif' => $tarif,
        //                 'daya' => $daya,
        //                 'unitup' => $unitup,
        //                 'kode_gardu' => $gardu,
        //                 'no_tiang' => $no_tiang,
        //                 'koordinat_x' => $koordinat_x,
        //                 'koordinat_y' => $koordinat_y
        //             ]

        //         );

        //         $analysis = MlAnalysis::create([

        //             'customer_id' => $customer->id,

        //             'avg_kwh' => $avg,

        //             'expected_kwh' => $json['expected_kwh'] ?? 0,

        //             'actual_kwh' => $actual,

        //             'loss_kwh' => $json['loss_kwh'] ?? 0,

        //             'risk_score' => $json['risk_score'] ?? 0,

        //             'revenue_at_risk' => $json['estimasi_rupiah'] ?? 0

        //         ]);

        //         $result[] = $analysis;
        //     }
        // }

        // return response()->json([

        //     'status' => 'success',
        //     'total_data' => count($result),
        //     'data' => $result

        // ]);
    }

    public function list()
    {

        // $data = MlAnalysis::with('customer')

        //     ->orderBy('risk_score', 'desc')

        //     ->limit(50)

        //     ->get();

        // return response()->json($data);
    }
}
