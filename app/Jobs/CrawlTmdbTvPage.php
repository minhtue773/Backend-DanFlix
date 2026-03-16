<?php

namespace App\Jobs;

use App\Models\Movie;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CrawlTmdbTvPage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $page;

    public function __construct($page)
    {
        $this->page = $page;
    }

    public function handle()
    {

        $apiKey = config('services.tmdb.key');

        $response = Http::get(
            "https://api.themoviedb.org/3/discover/tv",
            [
                'api_key' => $apiKey,
                'sort_by' => 'popularity.desc',
                'page' => $this->page
            ]
        );

        $tvs = $response->json('results');

        foreach ($tvs as $tv) {

            Movie::updateOrCreate(
                [
                    'tmdb_id' => $tv['id']
                ],
                [
                    'title' => $tv['name'],
                    'original_title' => $tv['original_name'],
                    'year' => substr($tv['first_air_date'] ?? '', 0, 4),
                    'poster_path' => $tv['poster_path'],
                    'backdrop_path' => $tv['backdrop_path'],
                    'type' => 'tv'
                ]
            );
        }

        echo "TV Page {$this->page} done\n";
    }
}