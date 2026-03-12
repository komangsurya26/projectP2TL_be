<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{
    protected $fillable = [
        'idpel',
        'nama',
        'notelp',
        'tarif',
        'daya',
        'nama_up',
        'unitup',
        'koordinat_x',
        'koordinat_y',
        'jenis_meter',
        'nometer'
    ];

    public function measurements()
    {
        return $this->hasMany(Measurement::class, 'idpel', 'idpel');
    }

    public function analisaResults()
    {
        return $this->hasMany(AnalisaResult::class, 'idpel', 'idpel');
    }
}
