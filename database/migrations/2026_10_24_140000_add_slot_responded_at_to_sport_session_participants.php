<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Trouver un créneau" : date à laquelle l'invité a donné ses disponibilités.
     * Permet de distinguer "n'a pas répondu" de "aucun créneau ne me va" (réponse sans créneau).
     */
    public function up(): void
    {
        Schema::table('sport_session_participants', function (Blueprint $table) {
            $table->timestamp('slot_responded_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('sport_session_participants', function (Blueprint $table) {
            $table->dropColumn('slot_responded_at');
        });
    }
};
