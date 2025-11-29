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
        Schema::table('sport_sessions', function (Blueprint $table) {
            // Ajouter les nouvelles colonnes
            $table->timestamp('start_date')->nullable()->after('sport');
            $table->timestamp('end_date')->nullable()->after('start_date');
        });

        // Migrer les données existantes : combiner date + start_time en start_date, date + end_time en end_date
        DB::statement("
            UPDATE sport_sessions 
            SET start_date = (date || ' ' || start_time)::timestamp,
                end_date = (date || ' ' || end_time)::timestamp
            WHERE date IS NOT NULL AND start_time IS NOT NULL AND end_time IS NOT NULL
        ");

        // Supprimer les anciennes colonnes
        Schema::table('sport_sessions', function (Blueprint $table) {
            $table->dropColumn(['date', 'start_time', 'end_time']);
        });

        // Rendre les nouvelles colonnes obligatoires
        Schema::table('sport_sessions', function (Blueprint $table) {
            $table->timestamp('start_date')->nullable(false)->change();
            $table->timestamp('end_date')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sport_sessions', function (Blueprint $table) {
            // Ajouter les anciennes colonnes
            $table->date('date')->after('sport');
            $table->time('start_time')->after('date');
            $table->time('end_time')->after('start_time');
        });

        // Migrer les données : extraire date et heure de start_date et end_date
        DB::statement("
            UPDATE sport_sessions 
            SET date = start_date::date,
                start_time = start_date::time,
                end_time = end_date::time
            WHERE start_date IS NOT NULL AND end_date IS NOT NULL
        ");

        // Supprimer les nouvelles colonnes
        Schema::table('sport_sessions', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};
