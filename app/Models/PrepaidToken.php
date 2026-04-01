<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrepaidToken extends Model
{
    protected $fillable = [
        'meter_id',
        'token_number',
        'purchase_date',
        'kwh_purchased',
        'amount_paid',
        'source'
    ];

    public function meter()
    {
        return $this->belongsTo(Meters::class, 'meter_id', 'id');
    }
}
