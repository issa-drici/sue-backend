<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_exercises', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('exercise_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->integer('watch_time')->default(0);
            $table->timestamps();

            // Modification de la contrainte unique pour inclure created_at
            $table->unique(['user_id', 'exercise_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_exercises');
    }
};
