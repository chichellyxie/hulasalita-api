<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Home', 'School', 'Food', 'Nature', 'Animals', 'Objects'] as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}
