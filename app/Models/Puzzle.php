<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Puzzle extends Model
{
    use HasFactory;

    public const TYPE_DAILY = 'daily';
    public const TYPE_BUGTONG = 'bugtong';
    public const TYPE_SCRAMBLE = 'scramble';
    public const TYPE_TRANSLATE = 'translate';

    public const TYPES = [
        self::TYPE_DAILY,
        self::TYPE_BUGTONG,
        self::TYPE_SCRAMBLE,
        self::TYPE_TRANSLATE,
    ];

    public const DIFFICULTY_EASY = 'easy';
    public const DIFFICULTY_MEDIUM = 'medium';
    public const DIFFICULTY_HARD = 'hard';

    /** Base points per difficulty (documentation, section 2.5). */
    public const BASE_POINTS = [
        self::DIFFICULTY_EASY => 10,
        self::DIFFICULTY_MEDIUM => 20,
        self::DIFFICULTY_HARD => 30,
    ];

    public const TIMEZONE = 'Asia/Manila';

    protected $fillable = [
        'language_id',
        'category_id',
        'type',
        'difficulty',
        'prompt',
        'hint',
        'answer',
        'max_tries',
        'scheduled_for',
    ];

    /**
     * ZERO CLIENT KNOWLEDGE: the answer is never included when a puzzle
     * is converted to an array/JSON. It is revealed only by the guess
     * endpoint after the puzzle is solved or all tries are used.
     */
    protected $hidden = [
        'answer',
    ];

    protected function casts(): array
    {
        return [
            'max_tries' => 'integer',
            'scheduled_for' => 'date',
        ];
    }

    /* ----------------------------------------------------------------
     | Relationships
     * ---------------------------------------------------------------- */

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    /* ----------------------------------------------------------------
     | Scopes
     * ---------------------------------------------------------------- */

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeOfDifficulty(Builder $query, string $difficulty): Builder
    {
        return $query->where('difficulty', $difficulty);
    }

    /** Filter by language code: en | fil | pag */
    public function scopeOfLanguage(Builder $query, string $code): Builder
    {
        return $query->whereHas('language', fn (Builder $q) => $q->where('code', $code));
    }

    /** Today's daily puzzle, using the Philippine calendar date. */
    public function scopeDailyForToday(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_DAILY)
            ->whereDate('scheduled_for', Carbon::now(self::TIMEZONE)->toDateString());
    }

    /* ----------------------------------------------------------------
     | Helpers (server-side only)
     * ---------------------------------------------------------------- */

    public function basePoints(): int
    {
        return self::BASE_POINTS[$this->difficulty] ?? 0;
    }

    /** Length of the answer — safe to send so the app can draw letter tiles. */
    public function answerLength(): int
    {
        return mb_strlen(self::normalize($this->answer));
    }

    /**
     * Compare a guess with the stored answer. Case, extra spaces and
     * accents (e.g. ñ → n) are ignored so "Danum", " danum " and "DANUM"
     * are all correct.
     */
    public function isCorrect(string $guess): bool
    {
        return self::normalize($guess) === self::normalize($this->answer);
    }

    public static function normalize(string $value): string
    {
        $value = Str::ascii($value);
        $value = mb_strtolower(trim($value));

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }
}
