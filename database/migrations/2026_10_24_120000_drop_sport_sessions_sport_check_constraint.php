<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sur PostgreSQL, l'enum initial de `sport` (5 sports) a créé une contrainte
     * CHECK que le passage en string (->change(), migration du 2025-10-01) n'a pas
     * supprimée : seuls tennis/golf/musculation/football/basketball étaient
     * insérables. La liste des sports est validée côté application (SportService).
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE sport_sessions DROP CONSTRAINT IF EXISTS sport_sessions_sport_check');
    }

    public function down(): void
    {
        // Pas de retour en arrière : recréer la contrainte bloquerait les sessions
        // existantes utilisant les autres sports.
    }
};
