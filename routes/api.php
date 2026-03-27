<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;



Route::get('/watch/{type}/{tmdb_id}', [MovieController::class, 'watch']);
Route::get('/detail/{type}/{tmdb_id}', [MovieController::class, 'detail']);


Route::get('/profile', [UserController::class, 'show'])->middleware('auth:sanctum');
Route::middleware('auth:sanctum', 'verified')->group(function () {
    Route::patch('/profile', [UserController::class, 'update']);
    Route::delete('/profile', [UserController::class, 'destroy']);
    Route::get('/user/watchlist', [UserController::class, 'watchlist']);
    Route::post('/user/watchlist', [UserController::class, 'addToWatchlist']);
    Route::delete('/user/watchlist', [UserController::class, 'removeFromWatchlist']);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/email/resend-otp', [AuthController::class, 'resendOtp'])
            ->middleware('throttle:3,1');
        Route::post('/email/verify-otp', [AuthController::class, 'verifyOtp']);
    });
});
