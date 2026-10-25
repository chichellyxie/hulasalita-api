<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportRequest;
use App\Models\Attempt;
use App\Models\School;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    /**
     * GET /api/v1/admin/reports?from=2026-10-01&to=2026-10-31
     * Summarized numbers only (no student details), per school and per game type.
     */
    public function index(ReportRequest $request): JsonResponse
    {
        $from = $request->input('from');
        $to = $request->input('to');

        $inRange = function (Builder $query) use ($from, $to) {
            return $query
                ->when($from, fn ($q) => $q->whereDate('attempts.played_on', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('attempts.played_on', '<=', $to));
        };

        $solvedSql = 'SUM(CASE WHEN attempts.solved = 1 THEN 1 ELSE 0 END)';

        // Overall totals
        $totals = $inRange(Attempt::query())
            ->selectRaw("COUNT(*) as attempts, {$solvedSql} as solved, COALESCE(SUM(attempts.points), 0) as points, COUNT(DISTINCT attempts.user_id) as players")
            ->first();

        // Per school
        $bySchool = $inRange(Attempt::query())
            ->join('users', 'users.id', '=', 'attempts.user_id')
            ->groupBy('users.school_id')
            ->selectRaw("users.school_id as school_id, COUNT(*) as attempts, {$solvedSql} as solved, COALESCE(SUM(attempts.points), 0) as points, COUNT(DISTINCT attempts.user_id) as players")
            ->get()
            ->keyBy('school_id');

        $schools = School::query()
            ->withCount(['users as verified_students' => fn ($q) => $q
                ->where('role', User::ROLE_STUDENT)
                ->where('status', User::STATUS_APPROVED)])
            ->orderBy('name')
            ->get()
            ->map(function (School $school) use ($bySchool) {
                $row = $bySchool->get($school->id);

                return [
                    'school_id' => $school->id,
                    'school' => $school->name,
                    'is_active' => $school->is_active,
                    'verified_students' => (int) $school->verified_students,
                    'players' => (int) ($row->players ?? 0),
                    'attempts' => (int) ($row->attempts ?? 0),
                    'solved' => (int) ($row->solved ?? 0),
                    'points' => (int) ($row->points ?? 0),
                ];
            });

        // Per game type and language
        $byType = $inRange(Attempt::query())
            ->join('puzzles', 'puzzles.id', '=', 'attempts.puzzle_id')
            ->join('languages', 'languages.id', '=', 'puzzles.language_id')
            ->groupBy('puzzles.type', 'languages.code')
            ->selectRaw("puzzles.type as type, languages.code as language, COUNT(*) as attempts, {$solvedSql} as solved")
            ->orderBy('puzzles.type')
            ->orderBy('languages.code')
            ->get()
            ->map(fn ($row) => [
                'type' => $row->type,
                'language' => $row->language,
                'attempts' => (int) $row->attempts,
                'solved' => (int) $row->solved,
                'solve_rate' => $row->attempts > 0 ? round($row->solved / $row->attempts * 100, 1) : 0,
            ]);

        $attempts = (int) ($totals->attempts ?? 0);
        $solved = (int) ($totals->solved ?? 0);

        return response()->json([
            'data' => [
                'range' => ['from' => $from, 'to' => $to],
                'totals' => [
                    'verified_students' => User::query()->verified()->count(),
                    'pending_verifications' => Verification::query()->pending()->whereNotNull('id_image_path')->count(),
                    'players' => (int) ($totals->players ?? 0),
                    'attempts' => $attempts,
                    'solved' => $solved,
                    'solve_rate' => $attempts > 0 ? round($solved / $attempts * 100, 1) : 0,
                    'points' => (int) ($totals->points ?? 0),
                ],
                'by_school' => $schools,
                'by_type' => $byType,
            ],
        ]);
    }
}
