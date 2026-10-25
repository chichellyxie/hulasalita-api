<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Puzzle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GuessTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->student = User::factory()->approved()->create();
        Sanctum::actingAs($this->student);
    }

    private function bugtong(array $attributes = []): Puzzle
    {
        return Puzzle::factory()->create(array_merge([
            'type' => Puzzle::TYPE_BUGTONG,
            'difficulty' => Puzzle::DIFFICULTY_MEDIUM,
            'answer' => 'langgam',
            'max_tries' => 4,
        ], $attributes));
    }

    private function guess(Puzzle $puzzle, string $guess)
    {
        return $this->postJson("/api/v1/puzzles/{$puzzle->id}/guess", ['guess' => $guess]);
    }

    public function test_daily_puzzle_never_contains_the_answer(): void
    {
        Puzzle::factory()->daily()->create(['answer' => 'danum']);

        $this->getJson('/api/v1/puzzles/daily')
            ->assertOk()
            ->assertJsonPath('data.answer_length', 5)
            ->assertJsonMissingPath('data.answer');
    }

    public function test_puzzle_list_and_detail_never_contain_the_answer(): void
    {
        $puzzle = $this->bugtong();

        $this->getJson('/api/v1/puzzles')->assertOk()->assertJsonMissingPath('data.0.answer');
        $this->getJson("/api/v1/puzzles/{$puzzle->id}")->assertOk()->assertJsonMissingPath('data.answer');
    }

    public function test_wrong_guess_uses_a_try_and_hides_the_answer(): void
    {
        $this->guess($this->bugtong(), 'aso')
            ->assertOk()
            ->assertJsonPath('data.correct', false)
            ->assertJsonPath('data.tries_left', 3)
            ->assertJsonPath('data.answer', null);
    }

    public function test_correct_guess_is_case_and_space_insensitive(): void
    {
        $this->guess($this->bugtong(), '  LangGam ')
            ->assertOk()
            ->assertJsonPath('data.correct', true)
            ->assertJsonPath('data.points_earned', 20);
    }

    public function test_each_failed_try_removes_one_fifth_of_the_points(): void
    {
        $puzzle = $this->bugtong(); // medium = 20

        $this->guess($puzzle, 'aso');
        $this->guess($puzzle, 'pusa');
        $this->guess($puzzle, 'langgam')
            ->assertJsonPath('data.points_earned', 12)   // 20 - 2 × 4
            ->assertJsonPath('data.answer', 'langgam');
    }

    public function test_points_never_drop_below_two_fifths(): void
    {
        $puzzle = $this->bugtong(['difficulty' => Puzzle::DIFFICULTY_HARD, 'max_tries' => 6]); // 30

        foreach (['a', 'b', 'c', 'd'] as $wrong) {
            $this->guess($puzzle, $wrong);
        }

        $this->guess($puzzle, 'langgam')->assertJsonPath('data.points_earned', 12); // 2/5 of 30
    }

    public function test_answer_is_revealed_after_the_last_try_and_puzzle_closes(): void
    {
        $puzzle = $this->bugtong(['max_tries' => 2]);

        $this->guess($puzzle, 'aso')->assertJsonPath('data.answer', null);
        $this->guess($puzzle, 'pusa')
            ->assertJsonPath('data.finished', true)
            ->assertJsonPath('data.solved', false)
            ->assertJsonPath('data.answer', 'langgam');

        $this->guess($puzzle, 'langgam')->assertStatus(422);
    }

    public function test_solved_puzzle_cannot_be_scored_twice(): void
    {
        $puzzle = $this->bugtong();
        $this->guess($puzzle, 'langgam')->assertOk();
        $this->guess($puzzle, 'langgam')->assertStatus(422);
    }

    public function test_daily_cap_is_150_points(): void
    {
        $puzzles = Puzzle::factory()->count(6)->create([
            'type' => Puzzle::TYPE_BUGTONG,
            'difficulty' => Puzzle::DIFFICULTY_HARD,   // 30 each
            'answer' => 'sagot',
        ]);

        foreach ($puzzles->take(5) as $puzzle) {
            $this->guess($puzzle, 'sagot')->assertJsonPath('data.points_earned', 30);
        }

        $this->guess($puzzles->last(), 'sagot')
            ->assertJsonPath('data.correct', true)
            ->assertJsonPath('data.points_earned', 0)
            ->assertJsonPath('data.points_today', 150)
            ->assertJsonPath('data.daily_cap_reached', true);
    }

    public function test_streak_grows_on_consecutive_days_only(): void
    {
        $yesterday = Carbon::now(Puzzle::TIMEZONE)->subDay()->toDateString();
        $this->student->forceFill(['current_streak' => 3, 'last_played_on' => $yesterday])->save();

        $this->guess($this->bugtong(), 'langgam')->assertJsonPath('data.current_streak', 4);

        // A second solve on the same day does not add to the streak.
        $this->guess($this->bugtong(['answer' => 'ilaw']), 'ilaw')->assertJsonPath('data.current_streak', 4);
    }

    public function test_streak_restarts_after_a_missed_day(): void
    {
        $twoDaysAgo = Carbon::now(Puzzle::TIMEZONE)->subDays(2)->toDateString();
        $this->student->forceFill(['current_streak' => 9, 'last_played_on' => $twoDaysAgo])->save();

        $this->guess($this->bugtong(), 'langgam')->assertJsonPath('data.current_streak', 1);
    }

    public function test_daily_word_guess_returns_letter_feedback(): void
    {
        $puzzle = Puzzle::factory()->daily()->create(['answer' => 'danum']);

        $this->guess($puzzle, 'dunam')
            ->assertOk()
            ->assertJsonPath('data.letters.0.state', 'correct')   // D
            ->assertJsonPath('data.letters.1.state', 'present')   // U
            ->assertJsonPath('data.letters.2.state', 'correct')   // N
            ->assertJsonPath('data.letters.3.state', 'present')   // A
            ->assertJsonPath('data.letters.4.state', 'correct');  // M
    }

    public function test_daily_guess_with_wrong_length_does_not_use_a_try(): void
    {
        $puzzle = Puzzle::factory()->daily()->create(['answer' => 'danum']);

        $this->guess($puzzle, 'dan')->assertStatus(422)->assertJsonValidationErrors('guess');
        $this->assertSame(0, Attempt::count());
    }

    public function test_future_daily_puzzle_cannot_be_played(): void
    {
        $tomorrow = Carbon::now(Puzzle::TIMEZONE)->addDay()->toDateString();
        $puzzle = Puzzle::factory()->daily($tomorrow)->create(['answer' => 'danum']);

        $this->getJson("/api/v1/puzzles/{$puzzle->id}")->assertNotFound();
        $this->guess($puzzle, 'danum')->assertStatus(422);
    }

    public function test_first_solve_awards_the_first_guess_badge(): void
    {
        $this->seed(\Database\Seeders\BadgeSeeder::class);

        $this->guess($this->bugtong(), 'langgam')
            ->assertJsonPath('data.new_badges.0.code', 'first_guess')
            ->assertJsonPath('data.new_badges.0.earned', true);

        $this->getJson('/api/v1/me/stats')
            ->assertOk()
            ->assertJsonPath('data.total_points', 20)
            ->assertJsonPath('data.puzzles_solved', 1)
            ->assertJsonPath('data.badges.0.earned', true);
    }
}
