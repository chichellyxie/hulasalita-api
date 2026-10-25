<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\Puzzle;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Checks a guess on the SERVER. The answer is compared here and is only
 * returned to the app after the puzzle is solved or all tries are used.
 *
 * One attempt row per student + puzzle. Its played_on moves to the date of
 * the latest guess, so points always count on the day they were earned.
 * A finished puzzle (solved or out of tries) cannot be played again.
 */
class GameService
{
    public function __construct(
        private readonly ScoringService $scoring,
        private readonly StreakService $streaks,
        private readonly BadgeService $badges,
    ) {}

    public function submitGuess(User $user, Puzzle $puzzle, string $guess): array
    {
        $now = Carbon::now(Puzzle::TIMEZONE);
        $today = $now->toDateString();

        if ($puzzle->type === Puzzle::TYPE_DAILY && $puzzle->scheduled_for?->toDateString() !== $today) {
            abort(422, 'This daily puzzle is not available today.');
        }

        // Daily Word Guess: a guess of the wrong length does not use a try.
        if ($puzzle->type === Puzzle::TYPE_DAILY
            && mb_strlen(Puzzle::normalize($guess)) !== $puzzle->answerLength()) {
            throw ValidationException::withMessages([
                'guess' => ["Your guess must have {$puzzle->answerLength()} letters."],
            ]);
        }

        return DB::transaction(function () use ($user, $puzzle, $guess, $now, $today) {
            // Lock the student's row so two quick taps cannot pass the daily cap.
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            $attempt = Attempt::query()
                ->where('user_id', $user->id)
                ->where('puzzle_id', $puzzle->id)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($attempt && $this->isFinished($attempt, $puzzle)) {
                abort(422, 'You have already finished this puzzle.');
            }

            $attempt ??= new Attempt([
                'user_id' => $user->id,
                'puzzle_id' => $puzzle->id,
                'tries' => 0,
                'solved' => false,
                'points' => 0,
            ]);

            $attempt->played_on = $today;
            $attempt->tries = $attempt->tries + 1;

            $correct = $puzzle->isCorrect($guess);
            $points = 0;
            $capReached = false;
            $newBadges = collect();

            if ($correct) {
                $rawPoints = $this->scoring->pointsFor($puzzle->difficulty, $attempt->tries);
                $earnedToday = $this->scoring->pointsEarnedOn($user, $today);
                $points = $this->scoring->applyDailyCap($rawPoints, $earnedToday);
                $capReached = $points < $rawPoints;

                $attempt->solved = true;
                $attempt->points = $points;
            }

            $attempt->save();

            if ($correct) {
                $this->streaks->recordPlay($user, $now);
                $newBadges = $this->badges->evaluate($user);
            }

            $finished = $this->isFinished($attempt, $puzzle);
            $pointsToday = $this->scoring->pointsEarnedOn($user, $today);

            return [
                'puzzle_id' => $puzzle->id,
                'correct' => $correct,
                'solved' => $attempt->solved,
                'finished' => $finished,
                'tries_used' => $attempt->tries,
                'tries_left' => max(0, $puzzle->max_tries - $attempt->tries),
                'max_tries' => $puzzle->max_tries,
                'points_earned' => $points,
                'base_points' => $puzzle->basePoints(),
                'points_today' => $pointsToday,
                'daily_cap' => ScoringService::DAILY_CAP,
                'daily_cap_reached' => $capReached || $pointsToday >= ScoringService::DAILY_CAP,
                'current_streak' => $this->streaks->effectiveStreak($user),
                'letters' => $puzzle->type === Puzzle::TYPE_DAILY
                    ? $this->letterFeedback($guess, $puzzle->answer)
                    : null,
                'answer' => $finished ? $puzzle->answer : null,
                'new_badges' => $newBadges,
            ];
        });
    }

    public function isFinished(Attempt $attempt, Puzzle $puzzle): bool
    {
        return $attempt->solved || $attempt->tries >= $puzzle->max_tries;
    }

    /**
     * Letter tiles for the Daily Word Guess:
     *   correct = right letter, right place
     *   present = letter is in the word, wrong place
     *   absent  = letter is not in the word (or already used up)
     *
     * @return array<int, array{letter:string, state:string}>
     */
    public function letterFeedback(string $guess, string $answer): array
    {
        $guessLetters = mb_str_split(Puzzle::normalize($guess));
        $answerLetters = mb_str_split(Puzzle::normalize($answer));

        $states = array_fill(0, count($guessLetters), 'absent');
        $remaining = [];

        foreach ($answerLetters as $i => $letter) {
            if (($guessLetters[$i] ?? null) === $letter) {
                $states[$i] = 'correct';
            } else {
                $remaining[$letter] = ($remaining[$letter] ?? 0) + 1;
            }
        }

        foreach ($guessLetters as $i => $letter) {
            if ($states[$i] === 'correct') {
                continue;
            }
            if (($remaining[$letter] ?? 0) > 0) {
                $states[$i] = 'present';
                $remaining[$letter]--;
            }
        }

        $result = [];
        foreach ($guessLetters as $i => $letter) {
            $result[] = ['letter' => mb_strtoupper($letter), 'state' => $states[$i]];
        }

        return $result;
    }
}
