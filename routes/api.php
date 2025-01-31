<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\SurlController;
use Illuminate\Support\Facades\Route;
use Utils\Base58;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::get('/surl', action: [SurlController::class, 'getUrlList']);
Route::get('/surl/{id}', action: [SurlController::class, 'getUrl']);
Route::delete('/surl/{id}', action: [SurlController::class, 'deleteUrl']);
Route::post('/surl', action: [SurlController::class, 'createUrlsFromFile']);

Route::get('/analytics/{id}', action: [AnalyticsController::class, 'getUrlAnalytics']);
Route::get('/analytics/t/{token}', action: [AnalyticsController::class, 'getUrlAnalyticsByToken'])
    ->where('token', Base58::$regex);
