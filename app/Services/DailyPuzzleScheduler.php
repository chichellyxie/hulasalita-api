<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Language;
use App\Models\Puzzle;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;

/**
 * Makes sure a date has a Daily Word Guess puzzle.
 *
 * - A puzzle the admin already scheduled for that date is always kept.
 * - Otherwise one is created from config/daily_words.php.
 * - Languages rotate day by day (English → Filipino → Pangasinan).
 * - A word is not reused within 60 days of the same word.
 */
class DailyPuzzleScheduler
{
    private const AVOID_REPEAT_DAYS = 60;

    private const ROTATION = ['en', 'fil', 'pag'];

    private const LANGUAGE_NAMES = ['en' => 'English', 'fil' => 'Filipino', 'pag' => 'Pangasinan'];

    /** Returns the daily puzzle for the date, creating it if needed (null if the bank is empty). */
    public function ensureFor(Carbon|string $date): ?Puzzle
    {
        $day = $date instanceof Carbon
            ? $date->copy()->setTimezone(Puzzle::TIMEZONE)->toDateString()
            : Carbon::parse($date, Puzzle::TIMEZONE)->toDateString();

        $existing = $this->findFor($day);
        if ($existing) {
            return $existing;
        }

        foreach ($this->languageOrderFor($day) as $code) {
            $language = Language::query()->where('code', $code)->first();
            $entry = $language ? $this->pickWord($code, $day) : null;

            if (! $language || ! $entry) {
                continue; // try the next language
            }

            [$answer, $hint, $categoryName, $difficulty] = $entry;
            $category = Category::query()->firstOrCreate(['name' => $categoryName]);
            $length = mb_strlen($answer);
            $languageName = self::LANGUAGE_NAMES[$code] ?? $language->name;

            try {
                return Puzzle::create([
                    'language_id' => $language->id,
                    'category_id' => $category->id,
                    'type' => Puzzle::TYPE_DAILY,
                    'difficulty' => $difficulty,
                    'prompt' => "Guess today's {$length}-letter {$languageName} word.",
                    'hint' => $hint,
                    'answer' => mb_strtolower($answer),
                    'max_tries' => 6,
                    'scheduled_for' => $day,
                ]);
            } catch (UniqueConstraintViolationException) {
                // Another request created it at the same moment — use that one.
                return $this->findFor($day);
            }
        }

        return null;
    }

    private function findFor(string $day): ?Puzzle
    {
        return Puzzle::query()
            ->where('type', Puzzle::TYPE_DAILY)
            ->whereDate('scheduled_for', $day)
            ->first();
    }

    /** Today's language first, then the others as fallbacks. */
    private function languageOrderFor(string $day): array
    {
        $dayNumber = intdiv(Carbon::parse($day, 'UTC')->startOfDay()->timestamp, 86400);
        $start = $dayNumber % count(self::ROTATION);

        return array_merge(
            array_slice(self::ROTATION, $start),
            array_slice(self::ROTATION, 0, $start),
        );
    }

    /** A word not used within ±60 days; if all were used, the least recently used one. */
    private function pickWord(string $code, string $day): ?array
    {
        $bank = config("daily_words.{$code}", []);
        if (empty($bank)) {
            return null;
        }

        $center = Carbon::parse($day, Puzzle::TIMEZONE);

        $nearby = Puzzle::query()
            ->where('type', Puzzle::TYPE_DAILY)
            ->whereDate('scheduled_for', '>=', $center->copy()->subDays(self::AVOID_REPEAT_DAYS)->toDateString())
            ->whereDate('scheduled_for', '<=', $center->copy()->addDays(self::AVOID_REPEAT_DAYS)->toDateString())
            ->pluck('answer')
            ->map(fn ($a) => Puzzle::normalize($a))
            ->all();

        $fresh = array_values(array_filter(
            $bank,
            fn ($entry) => ! in_array(Puzzle::normalize($entry[0]), $nearby, true)
        ));

        if (! empty($fresh)) {
            // Same date always gives the same pick (repeatable, but varied across days).
            return $fresh[crc32($day) % count($fresh)];
        }

        // Every word was used recently: take the one used longest ago.
        $lastUsed = Puzzle::query()
            ->where('type', Puzzle::TYPE_DAILY)
            ->whereNotNull('scheduled_for')
            ->get(['answer', 'scheduled_for'])
            ->groupBy(fn (Puzzle $p) => Puzzle::normalize($p->answer))
            ->map(fn ($group) => $group->max(fn (Puzzle $p) => $p->scheduled_for->toDateString()));

        usort($bank, fn ($a, $b) => strcmp(
            $lastUsed[Puzzle::normalize($a[0])] ?? '0000-00-00',
            $lastUsed[Puzzle::normalize($b[0])] ?? '0000-00-00',
        ));

        return $bank[0];
    }
}
