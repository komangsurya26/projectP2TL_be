<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Meters extends Model
{
    use HasFactory;

    protected $table = 'meters';

    protected $fillable = [
        'idpel',
        'meter_number',
        'meter_type',
        'tariff',
        'power_capacity'
    ];

    // Relasi Meter milik 1 Pelanggan
    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'idpel', 'idpel');
    }
}
