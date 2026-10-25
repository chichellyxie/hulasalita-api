<?php

namespace App\Console\Commands;

use App\Models\Puzzle;
use App\Services\DailyPuzzleScheduler;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * php artisan puzzles:schedule-daily            → makes sure TODAY has a daily puzzle
 * php artisan puzzles:schedule-daily --days=30  → fills today and the next 29 days
 *
 * Dates the admin already scheduled are never changed.
 */
class ScheduleDailyPuzzles extends Command
{
    protected $signature = 'puzzles:schedule-daily {--days=1 : How many days to fill, starting today (Asia/Manila)}';

    protected $description = 'Fill empty dates with a Daily Word Guess puzzle from the word bank';

    public function handle(DailyPuzzleScheduler $scheduler): int
    {
        $days = max(1, (int) $this->option('days'));
        $today = Carbon::now(Puzzle::TIMEZONE)->startOfDay();
        $rows = [];
        $created = 0;

        for ($i = 0; $i < $days; $i++) {
            $date = $today->copy()->addDays($i);
            $puzzle = $scheduler->ensureFor($date);

            if (! $puzzle) {
                $rows[] = [$date->toDateString(), '—', '—', 'Word bank is empty'];
                continue;
            }

            $isNew = $puzzle->wasRecentlyCreated;
            $created += $isNew ? 1 : 0;
            $rows[] = [
                $date->toDateString(),
                strtoupper($puzzle->answer),
                $puzzle->language?->code ?? '',
                $isNew ? 'created' : 'already scheduled',
            ];
        }

        $this->table(['Date', 'Answer', 'Language', 'Status'], $rows);
        $this->info("{$created} new daily puzzle(s) created.");

        return self::SUCCESS;
    }
}
