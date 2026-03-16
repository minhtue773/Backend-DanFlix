<?php

namespace App\Http\Controllers;

use App\Models\MovieStream;
use Illuminate\Support\Facades\Http;

class WatchController extends Controller
{
    public function watch($tmdbId)
    {
        $stream = MovieStream::where('tmdb_id', $tmdbId)->first();

        if (!$stream) {
            return response()->json([
                'error' => 'No stream found'
            ]);
        }

        $slug = $stream->slug;

        $response = Http::get("https://phimapi.com/phim/$slug");



        return $response->json();
    }
}