<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Puzzle;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    private function giveWeeklyPoints(User $user, int $points): void
    {
        Attempt::create([
            'user_id' => $user->id,
            'puzzle_id' => Puzzle::factory()->create()->id,
            'played_on' => Carbon::now(Puzzle::TIMEZONE)->toDateString(),
            'tries' => 1,
            'solved' => true,
            'points' => $points,
        ]);
    }

    public function test_leaderboard_shows_only_same_school_usernames_ranked_by_weekly_points(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();

        $me = User::factory()->approved()->create(['school_id' => $school->id, 'username' => 'ako']);
        $classmate = User::factory()->approved()->create(['school_id' => $school->id, 'username' => 'kaklase']);
        $outsider = User::factory()->approved()->create(['school_id' => $otherSchool->id, 'username' => 'taga_iba']);
        $pending = User::factory()->create(['school_id' => $school->id, 'username' => 'naghihintay']);

        $this->giveWeeklyPoints($me, 20);
        $this->giveWeeklyPoints($classmate, 30);
        $this->giveWeeklyPoints($classmate, 20);   // 2 solved puzzles, 50 points
        $this->giveWeeklyPoints($outsider, 99);
        $this->giveWeeklyPoints($pending, 80);

        Sanctum::actingAs($me);

        $response = $this->getJson('/api/v1/leaderboard')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.username', 'kaklase')
            ->assertJsonPath('data.0.rank', 1)
            ->assertJsonPath('data.0.points', 50)
            ->assertJsonPath('data.0.solved', 2)
            ->assertJsonPath('data.1.username', 'ako')
            ->assertJsonPath('data.1.is_me', true)
            ->assertJsonPath('meta.me.rank', 2);

        $response->assertDontSee('taga_iba')
            ->assertDontSee('naghihintay')
            ->assertDontSee($classmate->email);
    }
}
