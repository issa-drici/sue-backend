<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Trouver un créneau" : une session peut être créée sans date fixée, avec des créneaux proposés.
     * start_date / end_date restent renseignés (premier début / dernière fin des créneaux proposés)
     * pour que les listes, le tri et l'expiration du lien de partage continuent de fonctionner.
     */
    public function up(): void
    {
        Schema::table('sport_sessions', function (Blueprint $table) {
            // scheduled | finding_slot (string, pas enum : évite une contrainte CHECK PostgreSQL)
            $table->string('scheduling', 20)->default('scheduled')->after('status');
        });

        Schema::create('session_slot_proposals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('session_id');
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('sport_sessions')->onDelete('cascade');
            $table->index(['session_id', 'start_date']);
        });

        Schema::create('session_slot_availabilities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('slot_id');
            $table->uuid('user_id');
            $table->timestamps();

            $table->foreign('slot_id')->references('id')->on('session_slot_proposals')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['slot_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_slot_availabilities');
        Schema::dropIfExists('session_slot_proposals');
        Schema::table('sport_sessions', function (Blueprint $table) {
            $table->dropColumn('scheduling');
        });
    }
};
