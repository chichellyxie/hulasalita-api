<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    public const ROLE_STUDENT = 'student';
    public const ROLE_ADMIN = 'admin';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Only fields a student may set through registration / profile update.
     * role, status, is_active, current_streak and last_played_on are set
     * explicitly by the server (never mass-assigned from a request).
     */
    protected $fillable = [
        'school_id',
        'username',
        'email',
        'password',
    ];

    /**
     * Never serialized to JSON.
     */
    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'current_streak' => 'integer',
            'last_played_on' => 'date',
        ];
    }

    /* ----------------------------------------------------------------
     | Relationships
     * ---------------------------------------------------------------- */

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class);
    }

    /** The most recent school ID submission of this student. */
    public function latestVerification(): HasOne
    {
        return $this->hasOne(Verification::class)->latestOfMany();
    }

    /** Verifications this admin has reviewed. */
    public function reviewedVerifications(): HasMany
    {
        return $this->hasMany(Verification::class, 'reviewed_by');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
            ->using(UserBadge::class)
            ->withPivot('earned_at');
    }

    /* ----------------------------------------------------------------
     | Role / status helpers (used by middleware in Step 2)
     * ---------------------------------------------------------------- */

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    public function isPending(): bool
    {
        return $this->isStudent() && $this->status === self::STATUS_PENDING;
    }

    public function isVerifiedStudent(): bool
    {
        return $this->isStudent()
            && $this->status === self::STATUS_APPROVED
            && $this->is_active;
    }

    /* ----------------------------------------------------------------
     | Scopes
     * ---------------------------------------------------------------- */

    public function scopeStudents(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_STUDENT);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->students()
            ->where('status', self::STATUS_APPROVED)
            ->where('is_active', true);
    }
}
