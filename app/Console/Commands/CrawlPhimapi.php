<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Bus\Batch;
use Throwable;

use App\Jobs\CrawlPhimapiPage;

class CrawlPhimapi extends Command
{

    protected $signature = 'crawl:phimapi';

    protected $description = 'Crawl PhimAPI and map TMDB';

    public function handle()
    {

        $this->info("Preparing jobs...");

        $jobs = [];

        for ($page = 1; $page <= 2658; $page++) {

            $jobs[] = new CrawlPhimapiPage($page);
        }

        Bus::batch($jobs)

            ->then(function (Batch $batch) {

                echo "All PhimAPI pages crawled successfully!\n";
            })

            ->catch(function (Batch $batch, Throwable $e) {

                echo "Batch failed: " . $e->getMessage() . "\n";
            })

            ->finally(function (Batch $batch) {

                echo "Batch finished\n";
            })

            ->dispatch();

        $this->info("Batch dispatched with " . count($jobs) . " jobs.");
    }
}