<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CrawlTmdbTvPage;

class CrawlTmdbTv extends Command
{

    protected $signature = 'crawl:tmdb-tv';

    protected $description = 'Crawl TMDB TV shows';

    public function handle()
    {

        for ($page = 1; $page <= 500; $page++) {

            CrawlTmdbTvPage::dispatch($page);
        }

        $this->info("TV crawl jobs dispatched");
    }
}