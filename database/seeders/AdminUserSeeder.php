<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the administrator account. Credentials come from .env
 * (ADMIN_USERNAME, ADMIN_EMAIL, ADMIN_PASSWORD). Change the password
 * before any deployment.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrNew(['email' => env('ADMIN_EMAIL', 'admin@hulasalita.test')]);

        $admin->forceFill([
            'school_id' => null,
            'username' => env('ADMIN_USERNAME', 'admin'),
            'password' => env('ADMIN_PASSWORD', 'ChangeMe123!'),  // hashed by the model cast
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_APPROVED,
            'is_active' => true,
        ])->save();
    }
}
