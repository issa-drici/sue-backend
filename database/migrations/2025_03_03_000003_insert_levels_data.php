<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Générer des UUIDs pour les niveaux
        $level1Id = Str::uuid()->toString();
        $level2Id = Str::uuid()->toString();
        $level3Id = Str::uuid()->toString();
        $level4Id = Str::uuid()->toString();

        // Insérer les niveaux
        $levels = [
            [
                'id' => $level1Id,
                'name' => 'Niveau 1',
                'category' => 'beginner',
                'level_number' => 1,
                'description' => 'Premier niveau pour débutants',
                'banner_url' => 'https://example.com/banners/beginner_1.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $level2Id,
                'name' => 'Niveau 2',
                'category' => 'beginner',
                'level_number' => 2,
                'description' => 'Deuxième niveau pour débutants',
                'banner_url' => 'https://example.com/banners/beginner_2.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $level3Id,
                'name' => 'Niveau 3',
                'category' => 'intermediate',
                'level_number' => 3,
                'description' => 'Niveau intermédiaire',
                'banner_url' => 'https://example.com/banners/intermediate.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $level4Id,
                'name' => 'Niveau 4',
                'category' => 'advanced',
                'level_number' => 4,
                'description' => 'Niveau avancé',
                'banner_url' => 'https://example.com/banners/advanced.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('levels')->insert($levels);

        // Mettre à jour les exercices avec les level_id correspondants
        DB::table('exercises')
            ->where('level', 1)
            ->update(['level_id' => $level1Id]);

        DB::table('exercises')
            ->where('level', 2)
            ->update(['level_id' => $level2Id]);

        DB::table('exercises')
            ->where('level', 3)
            ->update(['level_id' => $level3Id]);

        DB::table('exercises')
            ->where('level', 4)
            ->update(['level_id' => $level4Id]);
    }

    public function down(): void
    {
        // Réinitialiser les level_id à null
        DB::table('exercises')->update(['level_id' => null]);

        // Supprimer les niveaux
        DB::table('levels')->truncate();
    }
};
