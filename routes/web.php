<?php

use App\Http\Controllers\P2TLImportController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\TemplateController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/phpinfo', function () {
    phpinfo();
});
