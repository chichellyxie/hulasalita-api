<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Order matters: puzzles need languages + categories,
        // demo students need schools.
        $this->call([
            SchoolSeeder::class,
            LanguageSeeder::class,
            CategorySeeder::class,
            BadgeSeeder::class,
            PuzzleSeeder::class,
            AdminUserSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call(DemoStudentSeeder::class);
        }
    }
}
