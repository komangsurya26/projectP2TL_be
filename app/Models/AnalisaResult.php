<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalisaResult extends Model
{
    protected $fillable = [
        'idpel',
        'risk_score',
        'status',
        'analisa_at',
    ];

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'idpel', 'idpel');
    }
}
