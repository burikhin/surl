<?php

use App\Http\Controllers\RedirectController;
use App\Http\Middleware\LogUrlRedirectVisit;
use Illuminate\Support\Facades\Route;
use Utils\Base58;

Route::get('/', function () {
    return view('welcome');
});

// Redirect route is restricted to only accept base58 encoded string as a token
Route::get('/r/{token}', action: [RedirectController::class, 'redirect'])
    ->where('token', Base58::$regex)->middleware(LogUrlRedirectVisit::class);
