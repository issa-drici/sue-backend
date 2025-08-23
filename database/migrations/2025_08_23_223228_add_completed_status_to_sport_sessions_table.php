<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pour PostgreSQL, nous devons d'abord supprimer la contrainte check existante
        // puis recréer l'enum avec les nouvelles valeurs
        DB::statement("ALTER TABLE sport_sessions DROP CONSTRAINT IF EXISTS sport_sessions_status_check");

        // Recréer l'enum avec les nouvelles valeurs
        DB::statement("ALTER TABLE sport_sessions ADD CONSTRAINT sport_sessions_status_check CHECK (status IN ('active', 'cancelled', 'completed'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer la contrainte check
        DB::statement("ALTER TABLE sport_sessions DROP CONSTRAINT IF EXISTS sport_sessions_status_check");

        // Recréer l'enum original
        DB::statement("ALTER TABLE sport_sessions ADD CONSTRAINT sport_sessions_status_check CHECK (status IN ('active', 'cancelled'))");
    }
};
