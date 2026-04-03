<?php

use App\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PrepaidTokenController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\MeterReadingController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\UploadHistoryController;
use App\Http\Controllers\AuthController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('jwt.cookie')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/upload-dil', [ImportController::class, 'uploadDil']);
    Route::post('/upload-ami', [ImportController::class, 'uploadAmi']);
    Route::post('/upload-amr', [ImportController::class, 'uploadAmr']);
    Route::post('/upload-epm', [ImportController::class, 'uploadEPM']);
    Route::post('/upload-prabayar', [ImportController::class, 'uploadPrabayar']);
    Route::post('/upload-sorek', [ImportController::class, 'uploadSorek']);
});

Route::get('/template-dil', [TemplateController::class, 'downloadDil']);
Route::get('/template-ami', [TemplateController::class, 'downloadAmi']);

Route::get('/pelanggan', [PelangganController::class, 'get']);
Route::get('/upload-history', [UploadHistoryController::class, 'get']);

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
