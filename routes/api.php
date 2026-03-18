<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PrepaidTokenController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\DILImportController;
use App\Http\Controllers\PelangganController;

Route::get('/download-template-dil', [TemplateController::class, 'download_dil']);
Route::get('/download-template-ami', [TemplateController::class, 'download_ami']);
Route::get('/download-template-amr', [TemplateController::class, 'download_amr']);


Route::post('/upload-dil', [DILImportController::class, 'upload']);

Route::get('/pelanggan', [PelangganController::class, 'get']);

Route::get('/meters/{meterNumber}/purchase-history', [PrepaidTokenController::class, 'getPurchaseHistory']);
