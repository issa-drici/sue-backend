<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Token opaque et non énumérable utilisé pour le partage / les Universal Links (/join/{token})
        Schema::table('sport_sessions', function (Blueprint $table) {
            $table->string('share_token', 64)->nullable()->unique()->after('status');
        });

        // Backfill : générer un token pour toutes les sessions existantes.
        // On récupère d'abord tous les ids, puis on met à jour (éviter de modifier
        // la colonne pendant qu'on itère dessus).
        $ids = DB::table('sport_sessions')->whereNull('share_token')->pluck('id');
        foreach ($ids as $id) {
            DB::table('sport_sessions')
                ->where('id', $id)
                ->update(['share_token' => Str::random(40)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sport_sessions', function (Blueprint $table) {
            $table->dropUnique(['share_token']);
            $table->dropColumn('share_token');
        });
    }
};
