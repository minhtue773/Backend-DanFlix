<?php

namespace App\Jobs;

use App\Models\MovieStream;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CrawlPhimapiDaily implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public function __construct(public int $page) {}

    public function handle()
    {
        $url = "https://phimapi.com/danh-sach/phim-moi-cap-nhat?page={$this->page}";

        $res = Http::timeout(10)
            ->retry(3, 1000)
            ->get($url)
            ->json();

        $items = $res['items'] ?? [];

        foreach ($items as $movie) {

            $slug = $movie['slug'];

            $type = $movie['tmdb']['type'] ?? 'movie';

            $season = null;

            if ($type === 'tv') {
                if (preg_match('/phan-(\d+)/', $slug, $m)) {
                    $season = (int)$m[1];
                } else {
                    $season = 1;
                }
            }

            $updateData = [
                'type' => $type,
                'season' => $season,
            ];

            // Chỉ thêm tmdb_id nếu có
            if (isset($movie['tmdb']['id'])) {
                $updateData['tmdb_id'] = $movie['tmdb']['id'];
            }

            MovieStream::updateOrCreate(
                [
                    'slug' => $slug,
                    'source' => 'phimapi'
                ],
                $updateData
            );

            // Nếu chưa có tmdb → dispatch match
            if (!isset($movie['tmdb']['id'])) {
                dispatch(new MatchSingleMovie($movie));
            }
        }

        echo "Page {$this->page} done\n";
    }
}
