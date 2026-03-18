<?php

use App\Http\Controllers\MovieController;
use App\Models\MovieStream;
use Illuminate\Support\Facades\Route;

Route::get('/watch/{type}/{tmdb_id}', [MovieController::class, 'watch']);
Route::get('/detail/{type}/{tmdb_id}', [MovieController::class, 'detail']);


Route::get('/updateDB', function () {


    $streams = MovieStream::where('type', 'tv')->get();

    foreach ($streams as $stream) {
        if (preg_match('/-phan-(\d+)$/', $stream->slug, $matches)) {
            $stream->season = intval($matches[1]);
        } else {
            $stream->season = 1;
        }
        $stream->save();
    }

    return 'Database updated successfully!';
});
