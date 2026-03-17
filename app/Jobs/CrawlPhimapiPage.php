<?php

namespace App\Jobs;

use App\Models\MovieStream;
use Illuminate\Bus\Queueable;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CrawlPhimapiPage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

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

        foreach ($items as $movie) {

            if (!isset($movie['tmdb']['id'])) continue;

            $tmdbId = $movie['tmdb']['id'];
            $slug = $movie['slug'];

            $type = $movie['tmdb']['type'] ?? 'movie';

            $season = null;

            if ($type === 'tv') {

                if (preg_match('/phan-(\d+)/', $slug, $match)) {
                    $season = (int)$match[1];
                } else {
                    $season = 1;
                }
            }

            MovieStream::updateOrCreate(

                [
                    'tmdb_id' => $tmdbId,
                    'type' => $type,
                    'season' => $season
                ],

                [
                    'slug' => $slug,
                    'source' => 'phimapi'
                ]

            );
        }
        echo "PhimAPI page {$this->page} done\n";
    }
}