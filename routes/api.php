<?php

use App\Http\Controllers\WatchController;
use Illuminate\Support\Facades\Route;

Route::get('/watch/{tmdb_id}', [WatchController::class, 'watch']);