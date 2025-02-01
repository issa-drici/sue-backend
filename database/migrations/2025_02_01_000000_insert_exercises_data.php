<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $exercisesData = [
            'levels' => [
                'beginner' => [
                    [
                        'name'             => 'Toe Taps',
                        'duration_seconds' => 135,
                        'xp'               => 25,
                        'thumbnail'        => 'https://i.ytimg.com/vi/O6d_bq_giOc/hqdefault.jpg',
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-1/Toe Taps.mp4',
                    ],
                    [
                        'name'             => 'Scissors',
                        'duration_seconds' => 135,
                        'xp'               => 25,
                        'thumbnail'        => 'https://i.ytimg.com/vi/rq8uWmO61ig/hqdefault.jpg',
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-1/Scissors.mp4',
                    ],
                    [
                        'name'             => 'Step Overs',
                        'duration_seconds' => 135,
                        'xp'               => 25,
                        'thumbnail'        => 'https://i.ytimg.com/vi/8IW0r1zBJOI/hqdefault.jpg',
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-1/Step Overs.mp4',
                    ],
                    [
                        'name'             => 'Outside Rolls',
                        'duration_seconds' => 242,
                        'xp'               => 25,
                        'thumbnail'        => 'https://i.ytimg.com/vi/bgvZ6Me--3A/hqdefault.jpg',
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-1/Outside Rolls.mp4',
                    ],
                    [
                        'name'             => 'Diagonal Roll',
                        'duration_seconds' => 241,
                        'xp'               => 25,
                        'thumbnail'        => 'https://i.ytimg.com/vi/WDUnmdHyjZk/hqdefault.jpg',
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-1/Diagonal Roll.mp4',
                    ],
                    [
                        'name'             => 'Diagonal Roll 2',
                        'duration_seconds' => 241,
                        'xp'               => 25,
                        'thumbnail'        => 'https://i.ytimg.com/vi/qsGzi15DehA/hqdefault.jpg',
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-1/Diagonal Roll 2.mp4',
                    ],
                    [
                        'name'             => 'Diagonal Roll 3',
                        'duration_seconds' => 242,
                        'xp'               => 25,
                        'thumbnail'        => 'https://i.ytimg.com/vi/p3tiFpowJCE/hqdefault.jpg',
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-1/Diagonal Roll 3.mp4',
                    ],
                    [
                        'name'             => 'Instep Roll',
                        'duration_seconds' => 238,
                        'xp'               => 25,
                        'thumbnail'        => 'https://i.ytimg.com/vi/zzjMrvoUt8Y/hqdefault.jpg',
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-1/Instep Roll.mp4',
                    ],
                    [
                        'name'             => 'Inside Touch',
                        'duration_seconds' => 133,
                        'xp'               => 25,
                        'thumbnail'        => 'https://i.ytimg.com/vi/csekDmJW2UU/hqdefault.jpg',
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-1/Inside Touch.mp4',
                    ],
                    [
                        'name'             => 'Sole Roll',
                        'duration_seconds' => 133,
                        'xp'               => 25,
                        'thumbnail'        => 'https://i.ytimg.com/vi/sPi1BXuzEnw/hqdefault.jpg',
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-1/Sole Roll.mp4',
                    ],
                    [
                        'name'             => 'Inside Touch 2',
                        'duration_seconds' => 130,
                        'xp'               => 25,
                        'thumbnail'        => 'https://i.ytimg.com/vi/_gmAEO5RieI/hqdefault.jpg',
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-1/Inside Touch 2.mp4',
                    ],
                    [
                        'name'             => 'V Shape',
                        'duration_seconds' => 130,
                        'xp'               => 25,
                        'thumbnail'        => 'https://i.ytimg.com/vi/xdThTLPLv38/hqdefault.jpg',
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-1/V Shape.mp4',
                    ],
                    [
                        'name'             => 'V Shape 2',
                        'duration_seconds' => 130,
                        'xp'               => 25,
                        'thumbnail'        => 'https://i.ytimg.com/vi/tZh48FfeQFM/hqdefault.jpg',
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-1/V Shape 2.mp4',
                    ],
                    [
                        'name'             => 'L Shape',
                        'duration_seconds' => 130,
                        'xp'               => 25,
                        'thumbnail'        => 'https://i.ytimg.com/vi/I3gQD09mxkE/hqdefault.jpg',
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-1/L Shape.mp4',
                    ],
                    [
                        'name'             => 'Double Tap Roll',
                        'duration_seconds' => 130,
                        'xp'               => 25,
                        'thumbnail'        => 'https://i.ytimg.com/vi/CIiBfcfWre8/hqdefault.jpg',
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-1/Double Tap Roll.mp4',
                    ],
                    [
                        'name'             => 'Sole Roll 2',
                        'duration_seconds' => 130,
                        'xp'               => 25,
                        'thumbnail'        => 'https://i.ytimg.com/vi/eETNRmGhI58/hqdefault.jpg',
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-1/Sole Roll 2.mp4',
                    ],
                    [
                        'name'             => 'Outside Touch',
                        'duration_seconds' => 136,
                        'xp'               => 25,
                        'thumbnail'        => 'https://i.ytimg.com/vi/sBVBKRHOBRc/hqdefault.jpg',
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-1/Outside Touch.mp4',
                    ],
                    [
                        'name'             => 'Scissors 2',
                        'duration_seconds' => 135,
                        'xp'               => 25,
                        // Pas de "thumbnail" dans le JSON
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-2/Scissors 2.mp4',
                    ],
                    [
                        'name'             => 'Step Overs 2',
                        'duration_seconds' => 135,
                        'xp'               => 25,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-2/Step Overs 2.mp4',
                    ],
                    [
                        'name'             => 'Scissors 3',
                        'duration_seconds' => 132,
                        'xp'               => 25,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-2/Scissors 3.mp4',
                    ],
                    [
                        'name'             => 'Outside Roll 2',
                        'duration_seconds' => 132,
                        'xp'               => 100,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-2/Outside Roll 2.mp4',
                    ],
                    [
                        'name'             => 'Outside Touch 2',
                        'duration_seconds' => 132,
                        'xp'               => 100,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-2/Outside Touch 2.mp4',
                    ],
                    [
                        'name'             => 'Outside Touch 3',
                        'duration_seconds' => 131,
                        'xp'               => 100,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-2/Outside Touch 3.mp4',
                    ],
                    [
                        'name'             => 'Instep Roll 2',
                        'duration_seconds' => 131,
                        'xp'               => 100,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-2/Instep Roll 2.mp4',
                    ],
                    [
                        'name'             => 'Mix Roll',
                        'duration_seconds' => 241,
                        'xp'               => 100,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-2/Mix Roll.mp4',
                    ],
                    [
                        'name'             => 'V Shape 3',
                        'duration_seconds' => 241,
                        'xp'               => 100,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-2/V Shape 3.mp4',
                    ],
                    [
                        'name'             => 'V Shape 4',
                        'duration_seconds' => 131,
                        'xp'               => 100,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-2/V Shape 4.mp4',
                    ],
                    [
                        'name'             => 'V Shape 5',
                        'duration_seconds' => 131,
                        'xp'               => 100,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-2/V Shape 5.mp4',
                    ],
                    [
                        'name'             => 'Inside Touch 3',
                        'duration_seconds' => 136,
                        'xp'               => 100,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-2/Inside Touch 3.mp4',
                    ],
                    [
                        'name'             => 'Inside Touch 4',
                        'duration_seconds' => 135,
                        'xp'               => 100,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-2/Inside Touch 4.mp4',
                    ],
                    [
                        'name'             => 'U Shape',
                        'duration_seconds' => 134,
                        'xp'               => 100,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-2/U Shape.mp4',
                    ],
                    [
                        'name'             => 'U Shape 2',
                        'duration_seconds' => 134,
                        'xp'               => 100,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-2/U Shape 2.mp4',
                    ],
                ],
                'intermediate' => [
                    [
                        'name'             => 'Sole Roll 3',
                        'duration_seconds' => 134,
                        'xp'               => 25,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-3/Sole Roll 3.mp4',
                    ],
                    [
                        'name'             => 'Outside Roll 3',
                        'duration_seconds' => 136,
                        'xp'               => 150,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-3/Outside Roll 3.mp4',
                    ],
                    [
                        'name'             => 'Figure 8',
                        'duration_seconds' => 132,
                        'xp'               => 150,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-3/Figure 8.mp4',
                    ],
                    [
                        'name'             => 'L Shape 2',
                        'duration_seconds' => 132,
                        'xp'               => 150,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-3/L Shape 2.mp4',
                    ],
                    [
                        'name'             => 'L Shape 3',
                        'duration_seconds' => 132,
                        'xp'               => 150,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-3/L Shape 3.mp4',
                    ],
                    [
                        'name'             => 'V Shape 6',
                        'duration_seconds' => 131,
                        'xp'               => 150,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-3/V Shape 6.mp4',
                    ],
                    [
                        'name'             => 'L + V Shape',
                        'duration_seconds' => 131,
                        'xp'               => 150,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-3/L + V Shape.mp4',
                    ],
                    [
                        'name'             => 'U Shape 3',
                        'duration_seconds' => 241,
                        'xp'               => 150,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-3/U Shape 3.mp4',
                    ],
                    [
                        'name'             => 'U Shape 4',
                        'duration_seconds' => 241,
                        'xp'               => 150,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-3/U Shape 4.mp4',
                    ],
                    [
                        'name'             => 'E Shape',
                        'duration_seconds' => 131,
                        'xp'               => 150,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-3/E Shape.mp4',
                    ],
                    [
                        'name'             => 'Triangle',
                        'duration_seconds' => 131,
                        'xp'               => 150,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-3/Triangle.mp4',
                    ],
                    [
                        'name'             => 'Triangle 2',
                        'duration_seconds' => 136,
                        'xp'               => 150,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-3/Triangle 2.mp4',
                    ],
                    [
                        'name'             => 'Triangle 3',
                        'duration_seconds' => 135,
                        'xp'               => 150,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-3/Triangle 3.mp4',
                    ],
                    [
                        'name'             => 'Triangle 4',
                        'duration_seconds' => 134,
                        'xp'               => 150,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-3/Triangle 4.mp4',
                    ],
                    [
                        'name'             => 'L + V Shape 2',
                        'duration_seconds' => 134,
                        'xp'               => 150,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-3/L + V Shape 2.mp4',
                    ],
                    [
                        'name'             => 'U + V Shape 2',
                        'duration_seconds' => 134,
                        'xp'               => 150,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-3/U + V Shape 2.mp4',
                    ],
                ],
                'advanced' => [
                    [
                        'name'             => 'Pro 1',
                        'duration_seconds' => 238,
                        'xp'               => 200,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-4/Pro 1.mp4',
                    ],
                    [
                        'name'             => 'Pro 2',
                        'duration_seconds' => 234,
                        'xp'               => 200,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-4/Pro 2.mp4',
                    ],
                    [
                        'name'             => 'Pro 3',
                        'duration_seconds' => 132,
                        'xp'               => 200,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-4/Pro 3.mp4',
                    ],
                    [
                        'name'             => 'Pro 4',
                        'duration_seconds' => 131,
                        'xp'               => 200,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-4/Pro 4.mp4',
                    ],
                    [
                        'name'             => 'Pro 5',
                        'duration_seconds' => 233,
                        'xp'               => 200,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-4/Pro 5.mp4',
                    ],
                    [
                        'name'             => 'Pro 6',
                        'duration_seconds' => 130,
                        'xp'               => 200,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-4/Pro 6.mp4',
                    ],
                    [
                        'name'             => 'Pro 7',
                        'duration_seconds' => 239,
                        'xp'               => 200,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-4/Pro 7.mp4',
                    ],
                    [
                        'name'             => 'Pro 8',
                        'duration_seconds' => 239,
                        'xp'               => 200,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-4/Pro 8.mp4',
                    ],
                    [
                        'name'             => 'Pro 9',
                        'duration_seconds' => 236,
                        'xp'               => 200,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-4/Pro 9.mp4',
                    ],
                    [
                        'name'             => 'Pro 10',
                        'duration_seconds' => 241,
                        'xp'               => 200,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-4/Pro 10.mp4',
                    ],
                    [
                        'name'             => 'Pro 11',
                        'duration_seconds' => 132,
                        'xp'               => 200,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-4/Pro 11.mp4',
                    ],
                    [
                        'name'             => 'Pro 12',
                        'duration_seconds' => 238,
                        'xp'               => 200,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-4/Pro 12.mp4',
                    ],
                    [
                        'name'             => 'Pro 13',
                        'duration_seconds' => 238,
                        'xp'               => 200,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-4/Pro 13.mp4',
                    ],
                    [
                        'name'             => 'Pro 14',
                        'duration_seconds' => 246,
                        'xp'               => 200,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-4/Pro 14.mp4',
                    ],
                    [
                        'name'             => 'Pro 15',
                        'duration_seconds' => 241,
                        'xp'               => 200,
                        'url'              => 'https://amb-habitat.alliance-tech.fr/prodribbler/level-4/Pro 15.mp4',
                    ],
                ],
            ],
        ];

        $levelMapping = [
            'beginner' => 1,
            'intermediate' => 2,
            'advanced' => 3,
        ];

        foreach ($exercisesData['levels'] as $levelName => $exercises) {
            $level = $levelMapping[$levelName];
            
            foreach ($exercises as $exercise) {
                DB::table('exercises')->insert([
                    'id' => Str::uuid(),
                    'level' => $level,
                    'banner_url' => $exercise['thumbnail'] ?? null,
                    'video_url' => $exercise['url'],
                    'title' => $exercise['name'],
                    'description' => null,
                    'duration' => $exercise['duration_seconds'],
                    'xp_value' => $exercise['xp'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('exercises')->truncate();
    }
}; 