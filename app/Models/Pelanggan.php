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
        'alamat',
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

    public function analisaResult()
    {
        return $this->hasOne(AnalisaResult::class, 'idpel', 'idpel');
    }
}
