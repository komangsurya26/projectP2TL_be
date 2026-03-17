<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeterAnalysis extends Model
{
    use HasFactory;

    protected $table = 'meter_analysis';

    protected $fillable = [
        'meter_id',
        'analysis_date',
        'consumption_kwh',
        'anomaly_status',
        'anomaly_score',
        'analysis_method'
    ];

    public function meter()
    {
        return $this->belongsTo(Meters::class, 'meter_id', 'id');
    }
}
