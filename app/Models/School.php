<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'barangay',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Only verified students — used by the same-school leaderboard. */
    public function verifiedStudents(): HasMany
    {
        return $this->hasMany(User::class)
            ->where('role', User::ROLE_STUDENT)
            ->where('status', User::STATUS_APPROVED)
            ->where('is_active', true);
    }

    /** Schools shown in the public registration list. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
