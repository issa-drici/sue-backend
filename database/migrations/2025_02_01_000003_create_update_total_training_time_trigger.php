<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Création de la fonction qui calcule le total du temps d'entraînement
        DB::unprepared('
            CREATE OR REPLACE FUNCTION calculate_total_training_time()
            RETURNS TRIGGER AS $$
            BEGIN
                UPDATE user_profiles
                SET total_training_time = (
                    SELECT COALESCE(SUM(watch_time), 0)
                    FROM user_exercises
                    WHERE user_id = COALESCE(NEW.user_id, OLD.user_id)
                )
                WHERE user_id = COALESCE(NEW.user_id, OLD.user_id);
                
                RETURN NULL;
            END;
            $$ LANGUAGE plpgsql;
        ');

        // Création du trigger qui s'exécute après INSERT, UPDATE ou DELETE
        DB::unprepared('
            CREATE TRIGGER update_total_training_time_trigger
            AFTER INSERT OR UPDATE OR DELETE ON user_exercises
            FOR EACH ROW
            EXECUTE FUNCTION calculate_total_training_time();
        ');
    }

    public function down(): void
    {
        // Suppression du trigger
        DB::unprepared('DROP TRIGGER IF EXISTS update_total_training_time_trigger ON user_exercises;');
        
        // Suppression de la fonction
        DB::unprepared('DROP FUNCTION IF EXISTS calculate_total_training_time();');
    }
}; 