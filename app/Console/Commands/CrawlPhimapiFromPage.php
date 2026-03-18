<?php

namespace App\Console\Commands;

use App\Jobs\CrawlPhimapiPage;
use Illuminate\Console\Command;

class CrawlPhimapiFromPage extends Command
{
    protected $signature = 'phimapi:crawl {start=260} {end=400}';

    public function handle()
    {
        $start = (int)$this->argument('start');
        $end   = (int)$this->argument('end');

        for ($i = $start; $i <= $end; $i++) {
            dispatch(new CrawlPhimapiPage($i));
        }

        $this->info("Dispatched from page {$start} to {$end}");
    }
}
