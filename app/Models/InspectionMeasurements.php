<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionMeasurements extends Model
{
    protected $table = 'inspection_measurements';

    protected $fillable = [
        'inspection_id',
        'voltage_r',
        'voltage_s',
        'voltage_t',
        'current_r',
        'current_s',
        'current_t',
        'power_factor',
        'deviasi',
        'faktor_kali',
    ];

    public function inspection()
    {
        return $this->belongsTo(Inspection::class);
    }
}
