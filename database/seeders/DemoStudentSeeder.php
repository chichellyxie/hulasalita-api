<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * LOCAL TESTING ONLY (called from DatabaseSeeder when APP_ENV=local).
 * Fictional accounts — password for all: password123
 *   - demo_verified  → approved, can play games
 *   - demo_pending   → pending, games return HTTP 403
 *   - demo_classmate → approved, same school (for leaderboard tests)
 */
class DemoStudentSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('name', 'Urbiztondo National High School')->firstOrFail();

        $students = [
            ['username' => 'demo_verified',  'email' => 'verified@hulasalita.test',  'status' => User::STATUS_APPROVED],
            ['username' => 'demo_pending',   'email' => 'pending@hulasalita.test',   'status' => User::STATUS_PENDING],
            ['username' => 'demo_classmate', 'email' => 'classmate@hulasalita.test', 'status' => User::STATUS_APPROVED],
        ];

        foreach ($students as $data) {
            $user = User::firstOrNew(['email' => $data['email']]);

            $user->forceFill([
                'school_id' => $school->id,
                'username' => $data['username'],
                'password' => 'password123',
                'role' => User::ROLE_STUDENT,
                'status' => $data['status'],
                'is_active' => true,
                'current_streak' => 0,
                'last_played_on' => null,
            ])->save();
        }
    }
}
