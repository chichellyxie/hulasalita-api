<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_register_and_starts_as_pending(): void
    {
        $school = School::factory()->create();

        $response = $this->postJson('/api/v1/auth/register', [
            'username' => 'juan_dc',
            'email' => 'juan@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'school_id' => $school->id,
            'privacy_accepted' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.username', 'juan_dc')
            ->assertJsonPath('user.status', 'pending')
            ->assertJsonPath('user.role', 'student')
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'school']])
            ->assertJsonMissingPath('user.password');

        $this->assertDatabaseHas('users', ['username' => 'juan_dc', 'status' => 'pending']);
    }

    public function test_registration_requires_an_active_school_and_privacy_consent(): void
    {
        $inactive = School::factory()->create(['is_active' => false]);

        $this->postJson('/api/v1/auth/register', [
            'username' => 'maria',
            'email' => 'maria@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'school_id' => $inactive->id,
        ])->assertStatus(422)->assertJsonValidationErrors(['school_id', 'privacy_accepted']);
    }

    public function test_user_can_log_in_with_username_or_email(): void
    {
        $user = User::factory()->create(['username' => 'pedro', 'email' => 'pedro@example.com']);

        $this->postJson('/api/v1/auth/login', ['login' => 'pedro', 'password' => 'password'])
            ->assertOk()->assertJsonStructure(['token', 'user']);

        $this->postJson('/api/v1/auth/login', ['login' => 'pedro@example.com', 'password' => 'password'])
            ->assertOk()->assertJsonPath('user.id', $user->id);
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create(['username' => 'pedro']);

        $this->postJson('/api/v1/auth/login', ['login' => 'pedro', 'password' => 'wrong-pass'])
            ->assertStatus(422)->assertJsonValidationErrors('login');
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        User::factory()->inactive()->create(['username' => 'pedro']);

        $this->postJson('/api/v1/auth/login', ['login' => 'pedro', 'password' => 'password'])
            ->assertForbidden();
    }

    public function test_requests_without_token_get_401(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }
}
