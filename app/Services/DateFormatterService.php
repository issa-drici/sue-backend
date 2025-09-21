<?php

namespace App\Services;

use DateTime;
use IntlDateFormatter;

class DateFormatterService
{
    private static array $sportNames = [
        'tennis' => 'Tennis',
        'golf' => 'Golf',
        'musculation' => 'Musculation',
        'football' => 'Football',
        'basketball' => 'Basketball'
    ];

    /**
     * Formate une date et heure en français
     * Exemple: "mardi 5 août à 10h30"
     */
    public static function formatDateAndTime(string $date, string $time): string
    {
        try {
            // Combiner la date et l'heure
            $dateTime = new DateTime($date . ' ' . $time);

            // Créer un formateur de date en français
            $formatter = new IntlDateFormatter(
                'fr_FR',
                IntlDateFormatter::FULL,
                IntlDateFormatter::SHORT,
                null,
                null,
                'EEEE d MMMM à HH:mm'
            );

            // Formater la date
            $formatted = $formatter->format($dateTime);

            // Remplacer les deux points par "h" pour l'heure
            return str_replace(':', 'h', $formatted);

        } catch (\Exception $e) {
            // Fallback en cas d'erreur
            return "le {$date} à {$time}";
        }
    }

    /**
     * Retourne le nom du sport en français avec majuscule
     */
    public static function getSportName(string $sport): string
    {
        return self::$sportNames[$sport] ?? ucfirst($sport);
    }

    /**
     * Génère un titre de notification pour une invitation
     * Exemple: "Invitation Tennis"
     */
    public static function generateInvitationTitle(string $sport): string
    {
        $sportName = self::getSportName($sport);
        return "Invitation {$sportName}";
    }

    /**
     * Formate une date en français
     * Exemple: "mardi 5 août"
     */
    public static function formatDate(string $date): string
    {
        try {
            $dateTime = new DateTime($date);
            $formatter = new IntlDateFormatter(
                'fr_FR',
                IntlDateFormatter::FULL,
                IntlDateFormatter::NONE,
                null,
                null,
                'EEEE d MMMM'
            );
            return $formatter->format($dateTime);
        } catch (\Exception $e) {
            return "le {$date}";
        }
    }

    /**
     * Formate une heure en français
     * Exemple: "10h30"
     */
    public static function formatTime(string $time): string
    {
        return str_replace(':', 'h', $time);
    }

    /**
     * Génère un message de notification pour une invitation
     * Exemple: "Vous avez été invité à une session de Tennis mardi 5 août de 10h30 à 12h30"
     */
    public static function generateInvitationMessage(string $sport, string $date, string $startTime, ?string $endTime = null): string
    {
        $sportName = self::getSportName($sport);
        $formattedDate = self::formatDate($date);
        $formattedStartTime = self::formatTime($startTime);

        if ($endTime) {
            $formattedEndTime = self::formatTime($endTime);
            return "Vous avez été invité à une session de {$sportName} {$formattedDate} de {$formattedStartTime} à {$formattedEndTime}";
        } else {
            return "Vous avez été invité à une session de {$sportName} {$formattedDate} à {$formattedStartTime}";
        }
    }

    /**
     * Génère un titre de notification push pour une invitation
     * Exemple: "🏃‍♂️ Invitation Tennis"
     */
    public static function generatePushInvitationTitle(string $sport): string
    {
        $sportName = self::getSportName($sport);
        return "🏃‍♂️ Invitation {$sportName}";
    }

    /**
     * Génère un titre de notification push pour une réinvitation
     * Exemple: "🏃‍♂️ Nouvelle invitation Tennis"
     */
    public static function generatePushReinvitationTitle(string $sport): string
    {
        $sportName = self::getSportName($sport);
        return "🏃‍♂️ Nouvelle invitation {$sportName}";
    }

    /**
     * Génère un titre de notification pour un commentaire
     * Exemple: "Commentaire Tennis"
     */
    public static function generateCommentTitle(string $sport): string
    {
        $sportName = self::getSportName($sport);
        return "Commentaire {$sportName}";
    }

    /**
     * Génère un titre de notification push pour un commentaire
     * Exemple: "💬 Commentaire Tennis"
     */
    public static function generatePushCommentTitle(string $sport): string
    {
        $sportName = self::getSportName($sport);
        return "💬 Commentaire {$sportName}";
    }

    /**
     * Génère un message de notification pour un commentaire
     * Exemple: "Jean Dupont a commenté votre session de Tennis lundi 5 août à 10h30"
     */
    public static function generateCommentMessage(string $authorName, string $sport, string $date, string $time): string
    {
        $sportName = self::getSportName($sport);
        $formattedDateTime = self::formatDateAndTime($date, $time);

        return "{$authorName} a commenté votre session de {$sportName} {$formattedDateTime}";
    }

    /**
     * Génère un message de notification pour un commentaire (version courte)
     * Exemple: "Jean Dupont a commenté la session de Tennis"
     */
    public static function generateCommentMessageShort(string $authorName, string $sport): string
    {
        $sportName = self::getSportName($sport);
        return "{$authorName} a commenté la session de {$sportName}";
    }

    /**
     * Formate une date de commentaire en français
     * Exemple: "lundi 5 août à 10h30"
     */
    public static function formatCommentDate(\DateTime $commentDate): string
    {
        return self::formatDateAndTime(
            $commentDate->format('Y-m-d'),
            $commentDate->format('H:i')
        );
    }
}
