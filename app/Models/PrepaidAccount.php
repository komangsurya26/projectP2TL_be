<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrepaidAccount extends Model
{
    protected $fillable = [
        'meter_id',
        'balance_kwh'
    ];

    public function meter()
    {
        return $this->belongsTo(Meters::class, 'meter_id', 'id');
    }
}
