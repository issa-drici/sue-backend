<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Trouver un créneau" : suivi des notifications envoyées à l'organisateur
     * (rappel pour confirmer un créneau, plus aucun créneau à venir).
     */
    public function up(): void
    {
        Schema::table('sport_sessions', function (Blueprint $table) {
            $table->timestamp('slot_reminder_sent_at')->nullable()->after('scheduling');
            $table->timestamp('slot_expired_notified_at')->nullable()->after('slot_reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('sport_sessions', function (Blueprint $table) {
            $table->dropColumn(['slot_reminder_sent_at', 'slot_expired_notified_at']);
        });
    }
};
