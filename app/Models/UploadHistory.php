<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadHistory extends Model
{
    protected $table = 'upload_histories';

    protected $fillable = [
        'filename',
        'status',
        'rows',
    ];
}
