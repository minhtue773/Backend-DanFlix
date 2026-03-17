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
    use Dispatchable, Queueable, SerializesModels, InteractsWithQueue;

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
                    'tmdb_id' => $movie['id'],
                    'type' => 'movie'
                ],

                [
                    'title' => $movie['title'],
                    'original_title' => $movie['original_title'],
                    'year' => substr($movie['release_date'] ?? '', 0, 4)
                ]

            );
        }

        echo "Movie page {$this->page} done\n";
    }
}