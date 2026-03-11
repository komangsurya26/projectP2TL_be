<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnomalyLog extends Model
{
    protected $fillable = [
        'idpel',
        'jenis_anomali',
        'nilai',
        'threshold',
        'sumber_data',
    ];

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'idpel', 'idpel');
    }
}
