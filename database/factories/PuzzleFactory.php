<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Language;
use App\Models\Puzzle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Puzzle>
 */
class PuzzleFactory extends Factory
{
    protected $model = Puzzle::class;

    public function definition(): array
    {
        return [
            'language_id' => Language::factory(),
            'category_id' => Category::factory(),
            'type' => Puzzle::TYPE_BUGTONG,
            'difficulty' => Puzzle::DIFFICULTY_EASY,
            'prompt' => fake()->sentence(),
            'hint' => fake()->sentence(4),
            'answer' => 'sagot',
            'max_tries' => 4,
            'scheduled_for' => null,
        ];
    }

    public function difficulty(string $difficulty): static
    {
        return $this->state(fn () => ['difficulty' => $difficulty]);
    }

    /** A daily puzzle scheduled for today (Asia/Manila) unless a date is given. */
    public function daily(?string $date = null): static
    {
        return $this->state(fn () => [
            'type' => Puzzle::TYPE_DAILY,
            'answer' => 'danum',
            'max_tries' => 6,
            'scheduled_for' => $date ?? Carbon::now(Puzzle::TIMEZONE)->toDateString(),
        ]);
    }
}
