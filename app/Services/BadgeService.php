<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\Badge;
use App\Models\Puzzle;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Awards badges whose rule the student now meets. Rule format (BadgeSeeder):
 *   puzzles_solved:N | streak:N | points_total:N | type_solved:<type>:N | languages_solved:N
 */
class BadgeService
{
    /**
     * @return Collection<int, Badge> badges earned by this call (empty if none)
     */
    public function evaluate(User $user): Collection
    {
        $earnedIds = $user->badges()->pluck('badges.id');
        $candidates = Badge::query()->whereNotIn('id', $earnedIds)->get();

        if ($candidates->isEmpty()) {
            return collect();
        }

        $stats = $this->statsFor($user);

        $newBadges = $candidates
            ->filter(fn (Badge $badge) => $this->meets($badge, $stats, $user))
            ->values();

        if ($newBadges->isNotEmpty()) {
            $now = now();
            $user->badges()->attach(
                $newBadges->mapWithKeys(fn (Badge $badge) => [$badge->id => ['earned_at' => $now]])->all()
            );

            // Not saved — lets BadgeResource show when the badge was earned.
            $newBadges->each(fn (Badge $badge) => $badge->setAttribute('earned_at', $now));
        }

        return $newBadges;
    }

    /**
     * Lifetime numbers of a student, also used by GET /me/stats.
     *
     * @return array{solved:int, points:int, by_type:array<string,int>, languages:int}
     */
    public function statsFor(User $user): array
    {
        $solved = Attempt::query()->where('user_id', $user->id)->where('solved', true);

        $byType = Attempt::query()
            ->join('puzzles', 'puzzles.id', '=', 'attempts.puzzle_id')
            ->where('attempts.user_id', $user->id)
            ->where('attempts.solved', true)
            ->groupBy('puzzles.type')
            ->selectRaw('puzzles.type as type, COUNT(*) as total')
            ->pluck('total', 'type');

        $languages = Attempt::query()
            ->join('puzzles', 'puzzles.id', '=', 'attempts.puzzle_id')
            ->where('attempts.user_id', $user->id)
            ->where('attempts.solved', true)
            ->distinct()
            ->count('puzzles.language_id');

        $types = [];
        foreach (Puzzle::TYPES as $type) {
            $types[$type] = (int) ($byType[$type] ?? 0);
        }

        return [
            'solved' => (clone $solved)->count(),
            'points' => (int) Attempt::query()->where('user_id', $user->id)->sum('points'),
            'by_type' => $types,
            'languages' => $languages,
        ];
    }

    private function meets(Badge $badge, array $stats, User $user): bool
    {
        $rule = $badge->parsedRule();

        return match ($rule['metric']) {
            'puzzles_solved' => $stats['solved'] >= $rule['threshold'],
            'streak' => $user->current_streak >= $rule['threshold'],
            'points_total' => $stats['points'] >= $rule['threshold'],
            'type_solved' => ($stats['by_type'][$rule['target']] ?? 0) >= $rule['threshold'],
            'languages_solved' => $stats['languages'] >= $rule['threshold'],
            default => false,
        };
    }
}
