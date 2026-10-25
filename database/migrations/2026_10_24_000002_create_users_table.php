<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accounts of students and administrators.
 * - role:   student | admin
 * - status: pending | approved | rejected  (verification status of a student)
 * - is_active: lets the admin deactivate/reactivate an account (PATCH /admin/users)
 * - current_streak / last_played_on: streak tracking in Asia/Manila dates
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Admins do not belong to a school, so the column is nullable.
            // restrictOnDelete: a school with students cannot be deleted
            // (the admin deactivates it with schools.is_active instead).
            $table->foreignId('school_id')
                ->nullable()
                ->constrained('schools')
                ->restrictOnDelete();

            $table->string('username', 30)->unique();
            $table->string('email', 150)->unique();
            $table->string('password');

            $table->enum('role', ['student', 'admin'])->default('student');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('is_active')->default(true);

            $table->unsignedInteger('current_streak')->default(0);
            $table->date('last_played_on')->nullable();

            $table->timestamps();

            $table->index(['role', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
