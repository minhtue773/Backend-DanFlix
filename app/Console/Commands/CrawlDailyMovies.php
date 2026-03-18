<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CrawlTmdbPage;
use App\Jobs\CrawlPhimapiDaily;
use App\Jobs\CrawlTmdbTvPage;

class CrawlDailyMovies extends Command
{
    // Tên command chạy bằng artisan
    protected $signature = 'movies:crawl-daily';

    // Mô tả command
    protected $description = 'Crawl 3 pages each day from TMDB and PhimAPI';

    public function handle()
    {
        $this->info("Dispatching TMDB pages...");

        // Crawl 3 pages từ TMDB
        for ($page = 1; $page <= 2; $page++) {
            CrawlTmdbPage::dispatch($page);
            CrawlTmdbTvPage::dispatch($page);
        }

        $this->info("Dispatching PhimAPI pages...");

        // Crawl 3 pages từ PhimAPI
        for ($page = 1; $page <= 3; $page++) {
            CrawlPhimapiDaily::dispatch($page);
        }

        $this->info("All jobs dispatched successfully.");
    }
}
