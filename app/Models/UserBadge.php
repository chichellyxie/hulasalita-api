<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot model for the user_badges table (composite key user_id + badge_id).
 */
class UserBadge extends Pivot
{
    protected $table = 'user_badges';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'badge_id',
        'earned_at',
    ];

    protected function casts(): array
    {
        return [
            'earned_at' => 'datetime',
        ];
    }
}
