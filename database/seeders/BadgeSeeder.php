<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

/**
 * Rule format (evaluated by the badge service in Step 2):
 *   puzzles_solved:N          total puzzles solved
 *   streak:N                  current_streak reaches N days
 *   points_total:N            lifetime points reach N
 *   type_solved:<type>:N      N puzzles solved of one type
 *   languages_solved:N        solved at least one puzzle in N languages
 */
class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            ['code' => 'first_guess',     'name' => 'Unang Hula',            'rule' => 'puzzles_solved:1'],
            ['code' => 'word_hunter',     'name' => 'Word Hunter',           'rule' => 'puzzles_solved:25'],
            ['code' => 'streak_3',        'name' => 'Tatlong Araw',          'rule' => 'streak:3'],
            ['code' => 'streak_7',        'name' => 'Isang Linggo',          'rule' => 'streak:7'],
            ['code' => 'streak_30',       'name' => 'Masipag na Manlalaro',  'rule' => 'streak:30'],
            ['code' => 'points_500',      'name' => 'Limang Daan',           'rule' => 'points_total:500'],
            ['code' => 'daily_devotee',   'name' => 'Daily Devotee',         'rule' => 'type_solved:daily:7'],
            ['code' => 'bugtong_master',  'name' => 'Bugtong Master',        'rule' => 'type_solved:bugtong:10'],
            ['code' => 'scramble_solver', 'name' => 'Scramble Solver',       'rule' => 'type_solved:scramble:10'],
            ['code' => 'translator',      'name' => 'Tagasalin',             'rule' => 'type_solved:translate:10'],
            ['code' => 'trilingual',      'name' => 'Tatlong Wika',          'rule' => 'languages_solved:3'],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(
                ['code' => $badge['code']],
                ['name' => $badge['name'], 'rule' => $badge['rule']],
            );
        }
    }
}
