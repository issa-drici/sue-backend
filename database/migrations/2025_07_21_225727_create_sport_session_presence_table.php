<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sport_session_presence', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sport_session_id');
            $table->uuid('user_id');
            $table->boolean('is_online')->default(true);
            $table->boolean('is_typing')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('typing_started_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('sport_session_id')->references('id')->on('sport_sessions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Unique constraint to prevent duplicate presence records
            $table->unique(['sport_session_id', 'user_id']);

            // Indexes
            $table->index(['sport_session_id', 'is_online']);
            $table->index(['sport_session_id', 'is_typing']);
            $table->index('last_seen_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sport_session_presence');
    }
};
