<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeterReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'meter_id',
        'reading_time',

        'import_kwh',
        'export_kwh',
        'kwh_total',
        'kvarh_total',

        'voltage_r',
        'voltage_s',
        'voltage_t',
        'current_r',
        'current_s',
        'current_t',

        'power_kw',
        'apparent_power',
        'power_factor',

        'source',
    ];

    public function meter()
    {
        return $this->belongsTo(Meters::class, 'meter_id', 'id');
    }
}
