<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Measurement extends Model
{
    protected $fillable = [
        'idpel',
        'jenis_meter',
        'waktu_data',
        'voltage_r',
        'voltage_s',
        'voltage_t',
        'current_r',
        'current_s',
        'current_t',
        'pf',
        'energy_import',
        'energy_export',
        'reactive_import',
        'reactive_export',
        'current_netral',
        'apparent_power'
    ];

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'idpel', 'idpel');
    }
}
