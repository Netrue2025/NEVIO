<?php

use App\Http\Controllers\PaystackController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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

// Template editor image upload
Route::middleware(['web', 'auth'])
    ->prefix('app')
    ->group(function () {
        Route::post('/upload-template-image', function (Request $request) {
            $request->validate([
                'image' => 'required|image|max:5120',
            ]);

            $path = $request->file('image')->store('template-uploads', 'public');

            return response()->json([
                'url' => Storage::url($path),
                'path' => $path,
            ]);
        })->name('upload.template.image');
    });
