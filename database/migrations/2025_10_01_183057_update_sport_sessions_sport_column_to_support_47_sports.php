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
        Schema::table('sport_sessions', function (Blueprint $table) {
            // Changer la colonne sport de enum vers string pour supporter les 48 sports
            $table->string('sport', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sport_sessions', function (Blueprint $table) {
            // Revenir à l'enum original avec les 5 sports
            $table->enum('sport', ['tennis', 'golf', 'musculation', 'football', 'basketball'])->change();
        });
    }
};
