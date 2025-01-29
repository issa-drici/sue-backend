<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('avatar_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->integer('total_xp')->default(0);
            $table->integer('total_training_time')->default(0);
            $table->integer('completed_videos')->default(0);
            $table->integer('completed_days')->default(0);
            $table->text('current_goals')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
}; 