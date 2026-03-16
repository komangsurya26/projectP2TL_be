<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{
    protected $fillable = [
        'idpel',
        'nama',
        'notelp',
        'alamat',
        'unitup',
        'koordinat_x',
        'koordinat_y'
    ];

    public function meters()
    {
        return $this->hasMany(Meters::class, 'idpel', 'idpel');
    }
}
