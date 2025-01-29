<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('exercise_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['user_id', 'exercise_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
}; 