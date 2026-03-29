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
        'meter_type',
        'tariff',
        'power_capacity'
    ];

    // Relasi Meter milik 1 Pelanggan
    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'idpel', 'idpel');
    }

    public function readings()
    {
        return $this->hasMany(MeterReading::class, 'meter_id', 'id');
    }

    public function prepaidAccount()
    {
        return $this->hasOne(PrepaidAccount::class, 'meter_id', 'id');
    }

    public function prepaidTokens()
    {
        return $this->hasMany(PrepaidToken::class, 'meter_id', 'id');
    }

    public function analysis()
    {
        return $this->hasMany(MeterAnalysis::class, 'meter_id', 'id');
    }
}
