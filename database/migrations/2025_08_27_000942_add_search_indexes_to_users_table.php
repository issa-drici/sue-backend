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
        Schema::table('users', function (Blueprint $table) {
            // Index pour la recherche par prénom (insensible à la casse)
            $table->index('firstname');

            // Index pour la recherche par nom (insensible à la casse)
            $table->index('lastname');

            // Index composite pour la recherche par prénom + nom
            $table->index(['firstname', 'lastname']);

            // Index pour la recherche par email (déjà unique, mais on peut ajouter un index pour les LIKE)
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['firstname']);
            $table->dropIndex(['lastname']);
            $table->dropIndex(['firstname', 'lastname']);
            $table->dropIndex(['email']);
        });
    }
};
