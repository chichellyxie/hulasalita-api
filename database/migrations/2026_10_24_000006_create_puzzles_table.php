<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daily words, bugtong, scrambles and translations.
 * - answer is HIDDEN in the Puzzle model and never leaves the server
 *   before the puzzle is solved or all tries are used.
 * - scheduled_for is used only by type = daily. It is UNIQUE so there is
 *   exactly one daily puzzle per date (MySQL allows many NULLs in a unique index).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puzzles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('language_id')
                ->constrained('languages')
                ->restrictOnDelete();

            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();

            $table->enum('type', ['daily', 'bugtong', 'scramble', 'translate']);
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('easy');

            $table->text('prompt');
            $table->string('hint')->nullable();
            $table->string('answer', 100);
            $table->unsignedTinyInteger('max_tries')->default(5);

            $table->date('scheduled_for')->nullable()->unique();

            $table->timestamps();

            $table->index(['type', 'language_id', 'difficulty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puzzles');
    }
};
