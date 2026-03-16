<?php

namespace App\Jobs;

use App\Models\MovieStream;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CrawlPhimapiPage implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $page;

    public function __construct($page)
    {
        $this->page = $page;
    }

    public function handle()
    {

        $url = "https://phimapi.com/danh-sach/phim-moi-cap-nhat?page=" . $this->page;

        $response = Http::get($url);

        $items = $response->json('items');

        if (!$items) return;
        echo "Crawling page " . $this->page . " with " . count($items) . " items\n";

        foreach ($items as $movie) {

            if (!isset($movie['tmdb']['id'])) continue;

            $tmdbId = $movie['tmdb']['id'];

            MovieStream::updateOrCreate(

                [
                    'tmdb_id' => $tmdbId
                ],

                [
                    'slug' => $movie['slug'],
                    'type' => $movie['tmdb']['type'] ?? 'movie',
                    'source' => 'phimapi'
                ]

            );

            echo "Saved TMDB " . $tmdbId . " -> " . $movie['slug'] . "\n";
        }
    }
}