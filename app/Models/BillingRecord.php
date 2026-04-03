<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingRecord extends Model
{
    protected $table = 'billing_records';

    protected $fillable = [
        'meter_id',
        'periode',
        'month',
        'year',
        'kwh_lwbp',
        'kwh_wbp',
        'kwh_total',
        'kvarh',
        'rpptl',
        'rpppn',
        'rpbpju',
        'tagihan',
        'status',
        'cost_per_kwh',
        'source'
    ];

    public function meter()
    {
        return $this->belongsTo(Meters::class);
    }
}
