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
        // Supprimer l'index unique temporairement si nécessaire
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
        });

        // Mettre à jour les valeurs null avec +33000000000 pour identifier les reliquats
        // Si plusieurs utilisateurs existent, on utilise un format séquentiel pour respecter l'unicité
        // Format: +33000000000, +33000000001, +33000000002, etc.
        $usersWithoutPhone = \DB::table('users')
            ->whereNull('phone')
            ->orderBy('created_at')
            ->get(['id']);

        $counter = 0;
        foreach ($usersWithoutPhone as $user) {
            if ($counter === 0) {
                // Premier utilisateur: +33000000000
                $phone = '+33000000000';
            } else {
                // Autres utilisateurs: +33000000001, +33000000002, etc. (max 99 pour rester dans le format)
                $phone = '+330000000' . str_pad($counter, 2, '0', STR_PAD_LEFT);
            }
            \DB::table('users')
                ->where('id', $user->id)
                ->update(['phone' => $phone]);
            $counter++;
        }

        // Rendre le champ obligatoire et réajouter l'index unique
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 50)->nullable(false)->change();
        });

        // Réajouter l'index unique après avoir rempli toutes les valeurs null
        Schema::table('users', function (Blueprint $table) {
            $table->unique('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->string('phone', 50)->nullable()->unique()->change();
        });
    }
};
