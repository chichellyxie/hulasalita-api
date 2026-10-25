<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Puzzle;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DailySchedulerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([LanguageSeeder::class, CategorySeeder::class]);
    }

    public function test_daily_endpoint_creates_todays_puzzle_when_none_is_scheduled(): void
    {
        Sanctum::actingAs(User::factory()->approved()->create());
        $this->assertSame(0, Puzzle::count());

        $this->getJson('/api/v1/puzzles/daily')
            ->assertOk()
            ->assertJsonPath('data.type', 'daily')
            ->assertJsonPath('data.scheduled_for', Carbon::now(Puzzle::TIMEZONE)->toDateString())
            ->assertJsonMissingPath('data.answer');

        // A second request returns the same puzzle (no duplicates).
        $this->getJson('/api/v1/puzzles/daily')->assertOk();
        $this->assertSame(1, Puzzle::where('type', 'daily')->count());
    }

    public function test_admin_scheduled_puzzle_is_kept(): void
    {
        Sanctum::actingAs(User::factory()->approved()->create());
        $mine = Puzzle::factory()->daily()->create([
            'language_id' => Language::where('code', 'pag')->value('id'),
            'answer' => 'danum',
        ]);

        $this->getJson('/api/v1/puzzles/daily')->assertOk()->assertJsonPath('data.id', $mine->id);
        $this->assertSame(1, Puzzle::where('type', 'daily')->count());
    }

    public function test_command_fills_upcoming_days_without_repeating_words(): void
    {
        $this->artisan('puzzles:schedule-daily', ['--days' => 21])->assertSuccessful();

        $daily = Puzzle::where('type', 'daily')->get();
        $this->assertCount(21, $daily);
        $this->assertCount(21, $daily->pluck('answer')->unique());
        $this->assertCount(3, $daily->pluck('language_id')->unique()); // en, fil, pag rotate

        // Running it again changes nothing.
        $this->artisan('puzzles:schedule-daily', ['--days' => 21])->assertSuccessful();
        $this->assertSame(21, Puzzle::where('type', 'daily')->count());
    }
}
