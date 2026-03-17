<?php

namespace App\Http\Controllers;

use App\Models\MovieStream;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;


class MovieController extends Controller
{
    public function watch($type, $id)
    {
        if (!in_array($type, ['movie', 'tv'])) {
            return response()->json([
                'error' => 'Invalid type'
            ], 400);
        }

        $season = request('season', 1);

        $cacheKey = "watch_{$type}_{$id}_season_{$season}";

        return Cache::remember($cacheKey, 1800, function () use ($type, $id, $season) {

            $apiKey = config('services.tmdb.key');

            // ⚡ gọi song song TMDB
            [$detail, $credits, $recommendations] = Http::pool(fn($pool) => [
                $pool->get("https://api.themoviedb.org/3/{$type}/{$id}", [
                    'api_key' => $apiKey,
                    'language' => 'vi-VN'
                ]),
                $pool->get("https://api.themoviedb.org/3/{$type}/{$id}/credits", [
                    'api_key' => $apiKey
                ]),
                $pool->get("https://api.themoviedb.org/3/{$type}/{$id}/recommendations", [
                    'api_key' => $apiKey
                ]),
            ]);

            if ($detail->failed()) {
                return response()->json([
                    'error' => 'TMDB error'
                ], 500);
            }

            $movie = $detail->json();
            $casts = $credits->json();
            $recommend = $recommendations->json();

            // 🎬 lấy stream từ DB
            $query = MovieStream::where('tmdb_id', $id)
                ->where('type', $type);

            if ($type === 'tv') {
                $query->where('season', $season);
            }

            $stream = $query->first();

            $episodes = [];

            if ($stream) {
                $slug = $stream->slug;

                // gọi phimapi
                $response = Http::get("https://phimapi.com/phim/{$slug}");

                if ($response->ok()) {
                    $data = $response->json();

                    // ⚠️ tùy cấu trúc phimapi (thường là episodes)
                    $episodes = $data['episodes'] ?? [];
                }
            }

            // 🎯 available seasons
            $availableSeasons = [];

            if ($type === 'tv') {
                $availableSeasons = MovieStream::where('tmdb_id', $id)
                    ->where('type', 'tv')
                    ->distinct()
                    ->pluck('season');
            }

            return response()->json([
                'movie' => $movie,

                'casts' => $casts['cast'] ?? [],

                'recommendations' => $recommend['results'] ?? [],

                'episodes' => $episodes,

                'available_seasons' => $availableSeasons,
            ]);
        });
    }


    public function seasons($tmdbId)
    {
        $seasons = MovieStream::where('tmdb_id', $tmdbId)
            ->where('type', 'tv')
            ->select('season')
            ->distinct()
            ->orderBy('season')
            ->pluck('season');

        return response()->json([
            'tmdb_id' => $tmdbId,
            'seasons' => $seasons
        ]);
    }
    public function detail($type, $id)
    {
        // validate type
        if (!in_array($type, ['movie', 'tv'])) {
            return response()->json([
                'error' => 'Invalid type'
            ], 400);
        }

        $cacheKey = "detail_{$type}_{$id}";

        return Cache::remember($cacheKey, 3600, function () use ($type, $id) {

            $apiKey = config('services.tmdb.key');

            $responses = Http::pool(fn($pool) => [
                $pool->get("https://api.themoviedb.org/3/{$type}/{$id}", [
                    'api_key' => $apiKey,
                    'language' => 'vi-VN'
                ]),
                $pool->get("https://api.themoviedb.org/3/{$type}/{$id}/credits", [
                    'api_key' => $apiKey
                ]),
                $pool->get("https://api.themoviedb.org/3/{$type}/{$id}/images", [
                    'api_key' => $apiKey
                ]),
                $pool->get("https://api.themoviedb.org/3/{$type}/{$id}/recommendations", [
                    'api_key' => $apiKey
                ]),
            ]);

            if ($responses[0]->failed()) {
                return response()->json(['error' => 'TMDB error'], 500);
            }

            $movie = $responses[0]->json();
            $casts = $responses[1]->json();
            $images = $responses[2]->json();
            $recommendations = $responses[3]->json();

            // 🎬 seasons có stream (DB của bạn)
            $availableSeasons = [];

            if ($type === 'tv') {
                $availableSeasons = MovieStream::where('tmdb_id', $id)
                    ->where('type', 'tv')
                    ->distinct()
                    ->pluck('season');
            }

            // 🎯 clean data trả về (rất quan trọng)
            return response()->json([
                'movie' => $movie,
                'casts' => $casts['cast'] ?? [],
                'images' => $images['backdrops'] ?? [],
                'recommendations' => $recommendations['results'] ?? [],
                'available_seasons' => $availableSeasons,
            ]);
        });
    }
}