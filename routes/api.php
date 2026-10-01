<?php

use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/register/admin', [AdminAuthController::class, 'register'])
        ->middleware('throttle:5,1')
        ->name('admin.register');
    Route::post('/login/admin', [AdminAuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('admin.login');

    Route::post('/register/{role}', [RegistrationController::class, 'storeApi'])
        ->where('role', 'dosen|mahasiswa')
        ->middleware('throttle:5,1')
        ->name('register');

    Route::prefix('admin')->name('admin.')->middleware([
        'auth:sanctum',
        'abilities:admin:access',
        'role:admin',
    ])->group(function () {
        Route::get('/me', [AdminAuthController::class, 'me'])->name('me');
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
    });
});
