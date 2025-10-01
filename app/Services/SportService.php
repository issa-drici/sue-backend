<?php

namespace App\Services;

class SportService
{
    /**
     * Liste complète des sports supportés (47 sports)
     * Triés par ordre alphabétique
     */
    public static function getSupportedSports(): array
    {
        $sports = [
            'aïkido',
            'aquafitness',
            'athlétisme',
            'aviron',
            'badminton',
            'baseball',
            'basketball',
            'bodyboard',
            'bowling',
            'boxe',
            'course',
            'cyclisme',
            'danse',
            'équitation',
            'escalade',
            'football',
            'golf',
            'gymnastique',
            'handball',
            'hockey',
            'jiu-jitsu-brésilien',
            'judo',
            'karaté',
            'kayak',
            'marche-nordique',
            'marche-sportive',
            'musculation',
            'natation',
            'padel',
            'pêche',
            'pétanque',
            'pilates',
            'ping-pong',
            'planche-à-voile',
            'randonnée',
            'rugby',
            'sauvetage-sportif',
            'ski',
            'skateboard',
            'snowboard',
            'squash',
            'stand-up-paddle',
            'surf',
            'tennis',
            'tir-à-l-arc',
            'triathlon',
            'volleyball',
            'yoga'
        ];

        // Trier alphabétiquement en ignorant les accents
        usort($sports, function($a, $b) {
            return strcoll(iconv('UTF-8', 'ASCII//TRANSLIT', $a), iconv('UTF-8', 'ASCII//TRANSLIT', $b));
        });

        return $sports;
    }

    /**
     * Vérifie si un sport est valide
     */
    public static function isValidSport(string $sport): bool
    {
        return in_array($sport, self::getSupportedSports());
    }

    /**
     * Retourne le nom formaté d'un sport (première lettre en majuscule)
     */
    public static function getFormattedSportName(string $sport): string
    {
        if (!self::isValidSport($sport)) {
            return $sport;
        }

        // Mapping spécial pour certains sports
        $specialMappings = [
            'jiu-jitsu-brésilien' => 'Jiu-jitsu brésilien',
            'marche-nordique' => 'Marche nordique',
            'marche-sportive' => 'Marche sportive',
            'planche-à-voile' => 'Planche à voile',
            'sauvetage-sportif' => 'Sauvetage sportif',
            'stand-up-paddle' => 'Stand up paddle',
            'tir-à-l-arc' => 'Tir à l\'arc',
            'ping-pong' => 'Ping-pong'
        ];

        if (isset($specialMappings[$sport])) {
            return $specialMappings[$sport];
        }

        // Capitalisation simple pour les autres sports
        return ucfirst($sport);
    }

    /**
     * Retourne la liste des sports pour la validation Laravel (format: sport1,sport2,sport3)
     */
    public static function getValidationRule(): string
    {
        return implode(',', self::getSupportedSports());
    }

    /**
     * Retourne les catégories de sports
     */
    public static function getSportCategories(): array
    {
        return [
            'sports-de-raquette' => [
                'tennis', 'padel', 'badminton', 'squash', 'ping-pong', 'volleyball', 'basketball', 'handball'
            ],
            'sports-aquatiques' => [
                'natation', 'surf', 'planche-à-voile', 'kayak', 'aviron', 'aquafitness', 'sauvetage-sportif', 'bodyboard'
            ],
            'sports-endurance' => [
                'course', 'cyclisme', 'randonnée', 'marche-nordique', 'marche-sportive', 'triathlon'
            ],
            'arts-martiaux' => [
                'boxe', 'jiu-jitsu-brésilien', 'aïkido', 'judo', 'karaté'
            ],
            'sports-glisse' => [
                'ski', 'snowboard', 'skateboard', 'stand-up-paddle'
            ],
            'sports-collectifs' => [
                'football', 'rugby', 'hockey', 'baseball', 'volleyball', 'handball'
            ],
            'sports-bien-etre' => [
                'yoga', 'pilates', 'danse'
            ],
            'sports-precision' => [
                'golf', 'tir-à-l-arc', 'pétanque'
            ],
            'autres' => [
                'musculation', 'escalade', 'équitation', 'gymnastique', 'athlétisme', 'bowling', 'pêche'
            ]
        ];
    }

    /**
     * Retourne la catégorie d'un sport
     */
    public static function getSportCategory(string $sport): ?string
    {
        $categories = self::getSportCategories();

        foreach ($categories as $category => $sports) {
            if (in_array($sport, $sports)) {
                return $category;
            }
        }

        return null;
    }
}
