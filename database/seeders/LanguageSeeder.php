<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['code' => Language::ENGLISH, 'name' => 'English'],
            ['code' => Language::FILIPINO, 'name' => 'Filipino'],
            ['code' => Language::PANGASINAN, 'name' => 'Pangasinan'],
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(['code' => $language['code']], ['name' => $language['name']]);
        }
    }
}
