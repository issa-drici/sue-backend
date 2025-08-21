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
        // Approche simple : modifier directement la contrainte CHECK
        // au lieu de modifier l'enum PostgreSQL

        // Supprimer la contrainte CHECK existante
        DB::statement("ALTER TABLE notifications DROP CONSTRAINT IF EXISTS notifications_type_check");

        // Ajouter une nouvelle contrainte CHECK qui inclut 'comment'
        DB::statement("ALTER TABLE notifications ADD CONSTRAINT notifications_type_check CHECK (type IN ('invitation', 'reminder', 'update', 'comment'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer la nouvelle contrainte
        DB::statement("ALTER TABLE notifications DROP CONSTRAINT IF EXISTS notifications_type_check");

        // Remettre l'ancienne contrainte
        DB::statement("ALTER TABLE notifications ADD CONSTRAINT notifications_type_check CHECK (type IN ('invitation', 'reminder', 'update'))");
    }
};
