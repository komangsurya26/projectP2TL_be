<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeterAnalysis extends Model
{
    use HasFactory;

    protected $table = 'meter_analysis';
    
    protected $casts = [
        'flags' => 'array',
    ];

    protected $fillable = [
        'meter_id',
        'analysis_date',
        'consumption_kwh',
        'avg_7_days',
        'avg_30_days',
        'zero_days_count',
        'anomaly_status',
        'anomaly_score',
        'flags',
        'analysis_method'
    ];

    public function meter()
    {
        return $this->belongsTo(Meters::class, 'meter_id', 'id');
    }
}
