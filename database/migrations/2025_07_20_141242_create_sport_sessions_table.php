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
        Schema::create('sport_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('sport', ['tennis', 'golf', 'musculation', 'football', 'basketball']);
            $table->date('date');
            $table->time('time');
            $table->string('location');
            $table->uuid('organizer_id');
            $table->timestamps();

            $table->foreign('organizer_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['sport', 'date']);
            $table->index('organizer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sport_sessions');
    }
};
