<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use App\Jobs\CrawlPhimapiPage;

class CrawlPhimapi extends Command
{
    protected $signature = 'crawl:phimapi {--from=1} {--to=2658}';
    protected $description = 'Crawl PhimAPI (production ready)';

    public function handle()
    {
        $from = (int) $this->option('from');
        $to = (int) $this->option('to');

        $chunkSize = 50;
        $delay = 0;

        $this->info("Start crawling from {$from} → {$to}");

        for ($i = $from; $i <= $to; $i += $chunkSize) {

            $jobs = [];

            for ($page = $i; $page < $i + $chunkSize && $page <= $to; $page++) {
                $jobs[] = (new CrawlPhimapiPage($page))
                    ->onQueue('crawl')
                    ->delay(now()->addSeconds($delay));
            }

            $start = $i;
            $end = min($i + $chunkSize - 1, $to);

            Bus::batch($jobs)
                ->name("Crawl {$start} - {$end}")
                ->allowFailures()
                ->then(fn() => Log::info("✅ Batch {$start}-{$end} done"))
                ->catch(fn($batch, $e) => Log::error("❌ Batch {$start}-{$end}: " . $e->getMessage()))
                ->dispatch();

            // 🔥 tránh spam DB + API
            $delay += 5;
        }

        $this->info("All batches dispatched!");
    }
}
