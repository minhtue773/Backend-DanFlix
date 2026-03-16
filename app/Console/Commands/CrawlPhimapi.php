<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CrawlPhimapiPage;

class CrawlPhimapi extends Command
{

    protected $signature = 'crawl:phimapi';

    protected $description = 'Crawl phimapi and map TMDB';

    public function handle()
    {

        $this->info("Dispatch crawl jobs...");

        for ($page = 501; $page <= 2658; $page++) {

            CrawlPhimapiPage::dispatch($page);
        }

        $this->info("Jobs dispatched");
    }
}