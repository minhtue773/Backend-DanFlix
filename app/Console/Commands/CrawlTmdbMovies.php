<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CrawlTmdbPage;
use App\Jobs\CrawlTmdbTvPage;

class CrawlTmdbMovies extends Command
{

    protected $signature = 'crawl:tmdb';

    protected $description = 'Crawl TMDB movies with queue';

    public function handle()
    {

        $this->info("Dispatching jobs...");

        for ($page = 1; $page <= 500; $page++) {
            CrawlTmdbPage::dispatch($page);
            CrawlTmdbTvPage::dispatch($page);
        }

        $this->info("1000 jobs dispatched!");
    }
}