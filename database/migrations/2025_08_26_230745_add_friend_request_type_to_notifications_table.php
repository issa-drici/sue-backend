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
        // Supprimer la contrainte enum existante
        DB::statement('ALTER TABLE notifications DROP CONSTRAINT IF EXISTS notifications_type_check');

        // Supprimer la colonne type
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        // Recréer la colonne avec le type friend_request ajouté
        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('type', [
                'invitation',
                'reminder',
                'update',
                'session_update',
                'session_cancelled',
                'friend_request',
                'comment'
            ])->default('update')->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer la contrainte enum
        DB::statement('ALTER TABLE notifications DROP CONSTRAINT IF EXISTS notifications_type_check');

        // Supprimer la colonne type
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        // Recréer la colonne sans friend_request
        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('type', [
                'invitation',
                'reminder',
                'update',
                'session_update',
                'session_cancelled'
            ])->default('update')->after('user_id');
        });
    }
};