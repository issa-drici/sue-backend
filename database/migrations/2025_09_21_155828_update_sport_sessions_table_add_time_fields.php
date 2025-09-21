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
            // Renommer la colonne time en startTime
            $table->renameColumn('time', 'start_time');
            
            // Ajouter la colonne endTime
            $table->time('end_time')->after('start_time');
            
            // Ajouter la colonne pricePerPerson
            $table->decimal('price_per_person', 10, 2)->nullable()->after('max_participants');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sport_sessions', function (Blueprint $table) {
            // Supprimer les nouvelles colonnes
            $table->dropColumn(['end_time', 'price_per_person']);
            
            // Renommer start_time en time
            $table->renameColumn('start_time', 'time');
        });
    }
};
