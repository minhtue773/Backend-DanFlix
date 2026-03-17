<?php

use App\Http\Controllers\MovieController;
use Illuminate\Support\Facades\Route;

Route::get('/watch/{type}/{tmdb_id}', [MovieController::class, 'watch']);
Route::get('/seasons/{tmdb_id}', [MovieController::class, 'seasons']);
Route::get('/detail/{type}/{tmdb_id}', [MovieController::class, 'detail']);