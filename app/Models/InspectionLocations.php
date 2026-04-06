<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionLocations extends Model
{
    protected $table = 'inspection_locations';

    protected $fillable = [
        'inspection_id',
        'latitude',
        'longitude'
    ];

    public function inspection()
    {
        return $this->belongsTo(Inspection::class);
    }
}
