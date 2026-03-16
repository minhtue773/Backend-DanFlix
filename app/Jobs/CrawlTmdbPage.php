<?php

namespace App\Jobs;

use App\Models\Movie;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CrawlTmdbPage implements ShouldQueue
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
            "https://api.themoviedb.org/3/discover/movie",
            [
                'api_key' => $apiKey,
                'sort_by' => 'popularity.desc',
                'page' => $this->page
            ]
        );

        $movies = $response->json('results');

        foreach ($movies as $movie) {

            Movie::updateOrCreate(
                [
                    'tmdb_id' => $movie['id']
                ],
                [
                    'title' => $movie['title'],
                    'original_title' => $movie['original_title'],
                    'year' => substr($movie['release_date'] ?? '', 0, 4),
                    'poster_path' => $movie['poster_path'],
                    'backdrop_path' => $movie['backdrop_path'],
                    'type' => 'movie'
                ]
            );
        }

        echo "Page {$this->page} done\n";
    }
}