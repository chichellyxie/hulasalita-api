<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Milestone definitions. `rule` is a machine-readable string that the
 * badge service (Step 2) evaluates, e.g.:
 *   puzzles_solved:1      streak:7      points_total:500
 *   type_solved:bugtong:10               languages_solved:3
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('rule', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};
