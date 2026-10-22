<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->middleware('prevent-back-history')
        ->name('login');

    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('auth/google/redirect', [GoogleController::class, 'redirect'])
        ->name('google.redirect');

    Route::get('auth/google/callback', [GoogleController::class, 'callback'])
        ->name('google.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
