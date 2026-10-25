<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    protected $model = School::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->lastName().' National High School',
            'barangay' => fake()->randomElement(['Poblacion', 'Real', 'Galarin', 'Bayaoas', 'Balangay']),
            'is_active' => true,
        ];
    }
}
