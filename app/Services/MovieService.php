<?php

namespace App\Services;

use App\Http\Resources\CastResource;
use App\Http\Resources\ImageResource;
use App\Http\Resources\MovieResource;
use App\Models\MovieStream;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;

class MovieService
{
    public function __construct() {}

    /**
     * Lấy chi tiết phim/TV, có cache 1 giờ
     */
    public function getDetail(string $type, int $id): array|JsonResponse
    {
        $cacheKey = "detail_{$type}_{$id}";

        $data = Cache::remember($cacheKey, 3600, function () use ($type, $id) {
            $detail = $this->fetchDetail($type, $id);
            if (!$detail) {
                return null; // sẽ xử lý bên ngoài, không cache response
            }

            $streams = MovieStream::where('tmdb_id', $id)
                ->where('type', $type)
                ->get();

            $hasStreaming = $streams->isNotEmpty();
            $availableSeasons = [];

            if ($hasStreaming && $type === 'tv') {
                $availableSeasons = $streams
                    ->pluck('season')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
            }

            // 🔥 Trả về array, merge với data từ fetchDetail
            return array_merge($detail, [
                'available_seasons' => $availableSeasons,
                'has_streaming' => $hasStreaming,
            ]);
        });

        if (!$data) {
            return response()->json(['error' => 'TMDB error'], 500);
        }

        return $data;
    }

    /**
     * Lấy dữ liệu để watch phim/TV, cache 30 phút
     */
    public function watch($type,  $id,  $season)
    {

        $cacheKey = $type === 'tv'
            ? "watch_{$type}_{$id}_season_{$season}"
            : "watch_{$type}_{$id}";

        return Cache::remember($cacheKey, 3600, function () use ($type, $id, $season) {

            $info = $this->getDetail($type, $id);
            if ($info instanceof JsonResponse) {
                return $info; // trả về lỗi nếu getDetail fail
            }

            $streamQuery = MovieStream::where('tmdb_id', $id)
                ->where('type', $type);

            if ($type === 'tv') {
                $streamQuery->where('season', $season);
            }

            $stream = $streamQuery->first();

            if (!$stream) {
                return response()->json([
                    'error' => 'No streaming available'
                ], 404);
            }

            $res = Http::get("https://phimapi.com/phim/{$stream->slug}");
            if (!$res->ok()) {
                return response()->json(['error' => 'Streaming API error'], 500);
            }

            $data = $res->json();

            return [
                'info' => $info,
                'episodes' => $data['episodes'] ?? [],
            ];
        });
    }

    /**
     * Fetch detail từ TMDB (movie/tv, credits, images, recommendations)
     */
    public function fetchDetail(string $type, int $id): ?array
    {
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

        if (!$responses[0]->ok()) {
            return null;
        }

        return [
            'movie' => new MovieResource($responses[0]->json()),
            'casts' => CastResource::collection($responses[1]->json()['cast'] ?? []),
            'images' => ImageResource::collection($responses[2]->json()['backdrops'] ?? []),
            'recommendations' => MovieResource::collection($responses[3]->json()['results'] ?? []),
        ];
    }
}
