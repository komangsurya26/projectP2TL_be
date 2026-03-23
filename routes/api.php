<?php

use App\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PrepaidTokenController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\MeterReadingController;
use App\Http\Controllers\PelangganController;

Route::get('/download-template-dil', [TemplateController::class, 'download_dil']);
Route::get('/download-template-ami', [TemplateController::class, 'download_ami']);
Route::get('/download-template-amr', [TemplateController::class, 'download_amr']);


Route::post('/upload-dil', [ImportController::class, 'uploadDil']);
Route::post('/upload-ami', [ImportController::class, 'uploadAmi']);


Route::get('/pelanggan', [PelangganController::class, 'get']);

//dil
Route::get('/meters/{idPel}/purchase-history', [PrepaidTokenController::class, 'getPurchaseHistory']);
Route::get('/meters/{idPel}/monthly-usage', [PrepaidTokenController::class, 'getMonthlyUsage']);
Route::get('/meters/{idPel}/token-trend', [PrepaidTokenController::class, 'getTokenTrend']);

//ami
Route::get('/meters/{idPel}/ami-usage', [MeterReadingController::class, 'getMonthlyUsageAMI']);
Route::get('/meters/{idPel}/ami-yearly-usage', [MeterReadingController::class, 'yearlyUsageAMI']);
Route::get('/meters/{idPel}/voltage-trend', [MeterReadingController::class, 'voltageTrend']);
Route::get('/meters/{idPel}/power-factor-trend', [MeterReadingController::class, 'powerFactorTrend']);
Route::get('/meters/{idPel}/measurement-history', [MeterReadingController::class, 'measurementHistory']);
