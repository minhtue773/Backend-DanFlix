<?php

namespace App\Jobs;

use App\Models\MovieStream;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MatchSingleMovie implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public int $timeout = 30;
    public int $tries = 3;

    public function __construct(public array $movie) {}

    public function handle(): void
    {
        $slug = $this->movie['slug'] ?? null;
        if (!$slug) return;

        $model = MovieStream::where('slug', $slug)->first();

        if (!$model || $model->tmdb_id) return;

        $rawTitle = $this->movie['origin_name']
            ?? $this->movie['name']
            ?? null;

        if (!$rawTitle) return;

        $cleanTitle = $this->cleanTitle($rawTitle);
        $year = $this->movie['year'] ?? null;

        Log::channel('tmdb')->info('START MATCH', [
            'slug' => $slug,
            'raw_title' => $rawTitle,
            'clean_title' => $cleanTitle,
            'year' => $year
        ]);

        // thử movie
        $match = $this->searchTmdb($cleanTitle, $year, 'movie');

        // fallback tv
        if (!$match) {
            Log::channel('tmdb')->info('FALLBACK TO TV', ['slug' => $slug]);
            $match = $this->searchTmdb($cleanTitle, $year, 'tv');
        }

        if (!$match) {
            Log::channel('tmdb')->warning('MATCH FAILED - NO RESULT', [
                'slug' => $slug,
                'title' => $cleanTitle
            ]);
            return;
        }

        [$best, $score, $type] = $match;

        if ($score < 80) {
            Log::channel('tmdb')->warning('MATCH FAILED - LOW SCORE', [
                'slug' => $slug,
                'best_title' => $best['title'] ?? $best['name'],
                'score' => $score
            ]);
            return;
        }

        // 🔁 reload lại model để tránh stale data
        $model->refresh();

        // ❌ nếu job khác đã update rồi thì skip
        if ($model->tmdb_id) {
            Log::channel('tmdb')->warning('SKIP - ALREADY MATCHED', [
                'slug' => $slug,
            ]);
            return;
        }

        // ✅ update có điều kiện (anti race condition)
        $updated = MovieStream::where('id', $model->id)
            ->whereNull('tmdb_id')
            ->update([
                'tmdb_id' => $best['id'],
                'type' => $type,
                'match_score' => $score,
                'matched_by' => 'clean_origin_name',
                'last_checked_at' => now()
            ]);

        // 🔍 check xem có update thật không
        if ($updated) {
            Log::channel('tmdb')->info('UPDATE SUCCESS', [
                'slug' => $slug,
                'tmdb_id' => $best['id']
            ]);
        } else {
            Log::channel('tmdb')->warning('UPDATE SKIPPED (RACE)', [
                'slug' => $slug,
            ]);
        }

        Log::channel('tmdb')->info('MATCH SUCCESS', [
            'slug' => $slug,
            'input' => $cleanTitle,
            'matched' => $best['title'] ?? $best['name'],
            'tmdb_id' => $best['id'],
            'score' => $score,
            'type' => $type
        ]);
    }

    private function searchTmdb(string $title, ?int $year, string $type): ?array
    {
        $cacheKey = "tmdb_v2_" . md5($type . $title . $year);

        $data = Cache::remember($cacheKey, 86400, function () use ($title, $year, $type) {

            $endpoint = "https://api.themoviedb.org/3/search/{$type}";

            $response = Http::timeout(10)->get($endpoint, [
                'api_key' => config('services.tmdb.key'),
                'query' => $title,
                'year' => $type === 'movie' ? $year : null,
                'first_air_date_year' => $type === 'tv' ? $year : null,
                'language' => 'en-US'
            ]);

            if (!$response->successful()) {
                Log::channel('tmdb')->error('TMDB API FAIL', [
                    'endpoint' => $endpoint,
                    'title' => $title
                ]);
                return [];
            }

            $results = $response->json()['results'] ?? [];

            Log::channel('tmdb')->debug('TMDB RAW RESULTS', [
                'title' => $title,
                'type' => $type,
                'count' => count($results)
            ]);

            return $results;
        });

        if (empty($data)) return null;

        $best = null;
        $bestScore = 0;

        foreach ($data as $r) {

            $titleField = $type === 'movie' ? 'title' : 'name';
            $originalField = $type === 'movie' ? 'original_title' : 'original_name';
            $dateField = $type === 'movie' ? 'release_date' : 'first_air_date';

            $candidateTitle = $r[$titleField] ?? '';

            $score = $this->score(
                $title,
                $candidateTitle,
                $r[$originalField] ?? '',
                $year,
                substr($r[$dateField] ?? '', 0, 4),
                $r['popularity'] ?? 0
            );

            Log::channel('tmdb')->debug('CANDIDATE', [
                'input' => $title,
                'candidate' => $candidateTitle,
                'score' => $score
            ]);

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $r;
            }
        }

        if (!$best) return null;

        Log::channel('tmdb')->info('BEST MATCH FOUND', [
            'title' => $title,
            'best' => $best['title'] ?? $best['name'],
            'score' => $bestScore,
            'type' => $type
        ]);

        return [$best, $bestScore, $type];
    }

    private function cleanTitle(string $str): string
    {
        $str = preg_replace('/\([^)]*\)/', '', $str);
        $str = preg_replace('/[-–:|].*/', '', $str);
        return trim($str);
    }

    private function normalize(string $str): string
    {
        $str = strtolower($str);
        $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str) ?: $str;
        return preg_replace('/[^a-z0-9]/', '', $str);
    }

    private function similarity(string $a, string $b): float
    {
        $maxLen = max(strlen($a), strlen($b));
        if ($maxLen === 0) return 0;

        $distance = levenshtein($a, $b);

        return (1 - $distance / $maxLen) * 100;
    }

    private function score(
        string $input,
        string $title,
        string $altTitle,
        ?int $y1,
        ?string $y2,
        float $popularity
    ): float {

        $inputN = $this->normalize($input);
        $titleN = $this->normalize($title);
        $altN = $this->normalize($altTitle);

        $simTitle = $this->similarity($inputN, $titleN);
        $simAlt = $this->similarity($inputN, $altN);

        $score = max($simTitle, $simAlt);

        $debug = [
            'input' => $input,
            'title' => $title,
            'alt' => $altTitle,
            'sim_title' => $simTitle,
            'sim_alt' => $simAlt,
            'base_score' => $score,
        ];

        if ($inputN === $titleN) {
            $score += 20;
            $debug['exact_match'] = true;
        }

        if ($y1 && $y2) {
            $diff = abs($y1 - (int)$y2);
            $debug['year_diff'] = $diff;

            if ($diff === 0) $score += 10;
            elseif ($diff === 1) $score += 5;
            elseif ($diff >= 3) $score -= 5;
        }

        $popBoost = min($popularity * 0.05, 5);
        $score += $popBoost;

        $debug['popularity_boost'] = $popBoost;
        $debug['final_score'] = $score;

        Log::channel('tmdb')->debug('SCORING DETAIL', $debug);

        return $score;
    }
}
