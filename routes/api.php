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

Route::get('/meters/{meterNumber}/purchase-history', [PrepaidTokenController::class, 'getPurchaseHistory']);
Route::get('/meters/{meterNumber}/monthly-usage', [PrepaidTokenController::class, 'getMonthlyUsage']);
Route::get('/meters/{meterNumber}/token-trend', [PrepaidTokenController::class, 'getTokenTrend']);

Route::get('/meters/{meterNumber}/ami-usage', [MeterReadingController::class, 'getMonthlyUsageAMI']);
Route::get('/meters/{meterNumber}/ami-yearly-usage', [MeterReadingController::class, 'yearlyUsageAMI']);
Route::get('/meters/{meterNumber}/voltage-trend', [MeterReadingController::class, 'voltageTrend']);
Route::get('/meters/{meterNumber}/power-factor-trend', [MeterReadingController::class, 'powerFactorTrend']);
Route::get('/meters/{meterNumber}/measurement-history', [MeterReadingController::class, 'measurementHistory']);
