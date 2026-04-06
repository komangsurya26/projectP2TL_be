<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inspection extends Model
{
    protected $table = 'inspections';

    protected $fillable = [
        'meter_id',
        'inspection_time',
        'stand_lwbp',
        'stand_wbp',
        'stand_kvarh',
        'status_kwh',
        'kode_pesan',
        'pemutusan',
        'rupiah_ts',
        'notes',
        'source',
    ];

    public function meter()
    {
        return $this->belongsTo(Meters::class);
    }
}
