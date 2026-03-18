<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovieResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isTv = !empty($this['name']);

        return [
            'id' => $this['id'] ?? null,

            // 🔥 unify field
            'type' => $isTv ? 'tv' : 'movie',
            'title' => $this['title'] ?? $this['name'] ?? null,
            'original_title' => $this['original_title'] ?? $this['original_name'] ?? null,
            'overview' => $this['overview'] ?? null,

            'date' => $this['release_date'] ?? $this['first_air_date'] ?? null,

            'poster' => $this['poster_path']
                ? $this['poster_path']
                : null,

            'backdrop' => $this['backdrop_path']
                ? $this['backdrop_path']
                : null,
            'rating' => $this['vote_average'] ?? null,
            'genres' => collect($this['genres'] ?? [])->pluck('name'),
            'origin_country' => $this['origin_country'] ?? null,

            // 🔥 runtime unified
            'duration' => $this['runtime']
                ?? ($this['episode_run_time'][0] ?? null),

            // 🔥 chỉ hiện khi là TV
            'seasons' => $this->when($isTv, [
                'count' => $this['number_of_seasons'] ?? null,
                'episodes' => $this['number_of_episodes'] ?? null,
                'list' => collect($this['seasons'] ?? [])->map(fn($season) => [
                    'id' => $season['id'] ?? null,
                    'name' => $season['name'] ?? null,
                    'season_number' => $season['season_number'] ?? null,
                    'episode_count' => $season['episode_count'] ?? null,
                    'poster' => $season['poster_path']
                        ? $season['poster_path']
                        : null,
                ]),
            ]),
        ];
    }
}
