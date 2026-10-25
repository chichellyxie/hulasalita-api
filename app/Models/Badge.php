<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'rule',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_badges')
            ->using(UserBadge::class)
            ->withPivot('earned_at');
    }

    /**
     * Split the rule string into its parts.
     *   "streak:7"               → ['metric' => 'streak', 'target' => null, 'threshold' => 7]
     *   "type_solved:bugtong:10" → ['metric' => 'type_solved', 'target' => 'bugtong', 'threshold' => 10]
     */
    public function parsedRule(): array
    {
        $parts = explode(':', $this->rule);

        if (count($parts) === 3) {
            return ['metric' => $parts[0], 'target' => $parts[1], 'threshold' => (int) $parts[2]];
        }

        return ['metric' => $parts[0], 'target' => null, 'threshold' => (int) ($parts[1] ?? 0)];
    }
}
