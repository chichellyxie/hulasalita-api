<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One student playing one puzzle on one day (Asia/Manila date).
 * Source of truth for scores, the 150-point daily cap and the leaderboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('puzzle_id')
                ->constrained('puzzles')
                ->cascadeOnDelete();

            $table->date('played_on');
            $table->unsignedTinyInteger('tries')->default(0);
            $table->boolean('solved')->default(false);
            $table->unsignedSmallInteger('points')->default(0);

            $table->timestamps();

            // Only one attempt per user, puzzle and day.
            $table->unique(['user_id', 'puzzle_id', 'played_on']);

            // Daily cap lookups (user + day) and weekly leaderboard (by date range).
            $table->index(['user_id', 'played_on']);
            $table->index('played_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempts');
    }
};
