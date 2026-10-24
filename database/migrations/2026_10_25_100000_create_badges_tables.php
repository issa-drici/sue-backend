<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Badges : ceux débloqués par chaque joueur (et s'il a vu la célébration),
     * et les parrainages (nouvel inscrit arrivé par le lien de partage de quelqu'un).
     */
    public function up(): void
    {
        Schema::create('user_badges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('badge', 40);
            $table->timestamp('unlocked_at');
            $table->timestamp('seen_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'badge']);
        });

        Schema::create('user_referrals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('referrer_id');
            $table->uuid('referred_id')->unique(); // une personne ne compte qu'une fois
            $table->uuid('session_id')->nullable();
            $table->timestamps();

            $table->foreign('referrer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('referred_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('referrer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_referrals');
        Schema::dropIfExists('user_badges');
    }
};
