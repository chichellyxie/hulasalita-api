<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Attempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'puzzle_id',
        'played_on',
        'tries',
        'solved',
        'points',
    ];

    protected function casts(): array
    {
        return [
            'played_on' => 'date',
            'tries' => 'integer',
            'solved' => 'boolean',
            'points' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function puzzle(): BelongsTo
    {
        return $this->belongsTo(Puzzle::class);
    }

    /** Attempts played on a given Asia/Manila date (default: today). */
    public function scopeOnDay(Builder $query, ?Carbon $day = null): Builder
    {
        $day ??= Carbon::now(Puzzle::TIMEZONE);

        return $query->whereDate('played_on', $day->toDateString());
    }

    /** Attempts in the current Monday–Sunday week (Asia/Manila). */
    public function scopeThisWeek(Builder $query): Builder
    {
        $now = Carbon::now(Puzzle::TIMEZONE);

        return $query->whereBetween('played_on', [
            $now->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
            $now->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
        ]);
    }

    public function scopeSolved(Builder $query): Builder
    {
        return $query->where('solved', true);
    }
}
