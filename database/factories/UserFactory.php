<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * Replaces Laravel's default UserFactory (which uses `name` and
 * `email_verified_at`, columns HulaSalita does not have).
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'username' => fake()->unique()->regexify('[a-z]{5}[0-9]{3}'),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => User::ROLE_STUDENT,
            'status' => User::STATUS_PENDING,
            'is_active' => true,
            'current_streak' => 0,
            'last_played_on' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => User::STATUS_APPROVED]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => User::STATUS_REJECTED]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'school_id' => null,
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_APPROVED,
        ]);
    }
}
