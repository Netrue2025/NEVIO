<?php

use App\Http\Controllers\PaystackController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/user');
});

// Paystack routes
Route::get('/paystack/callback', [PaystackController::class, 'callback'])
    ->name('paystack.callback')
    ->middleware('web');

Route::post('/paystack/webhook', [PaystackController::class, 'webhook'])
    ->name('paystack.webhook')
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
