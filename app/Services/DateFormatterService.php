<?php

namespace App\Services;

use DateTime;
use IntlDateFormatter;

class DateFormatterService
{

    /**
     * Formate une date et heure en français
     * Exemple: "mardi 5 août à 10h30"
     */
    public static function formatDateAndTime(string $date, string $time): string
    {
        try {
            // Les dates/heures reçues sont en Europe/Paris (depuis les getters de SportSession)
            $dateTime = new DateTime($date . ' ' . $time, new \DateTimeZone('Europe/Paris'));

            // Créer un formateur de date en français
            $formatter = new IntlDateFormatter(
                'fr_FR',
                IntlDateFormatter::FULL,
                IntlDateFormatter::SHORT,
                \IntlTimeZone::createTimeZone('Europe/Paris'),
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
        return SportService::getFormattedSportName($sport);
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
            // Les dates reçues sont en Europe/Paris (depuis les getters de SportSession)
            $dateTime = new DateTime($date, new \DateTimeZone('Europe/Paris'));
            $formatter = new IntlDateFormatter(
                'fr_FR',
                IntlDateFormatter::FULL,
                IntlDateFormatter::NONE,
                \IntlTimeZone::createTimeZone('Europe/Paris'),
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
        try {
            // Parser l'heure avec DateTime (gère H:i et H:i:s)
            $dateTime = DateTime::createFromFormat('H:i:s', $time);
            if (!$dateTime) {
                $dateTime = DateTime::createFromFormat('H:i', $time);
            }

            if (!$dateTime) {
                // Fallback si le format n'est pas reconnu
                return str_replace(':', 'h', $time);
            }

            // Formater en "Hhmm"
            return $dateTime->format('H\hi');
        } catch (\Exception $e) {
            // Fallback en cas d'erreur
            return str_replace(':', 'h', $time);
        }
    }

    /**
     * Génère un message de notification pour une invitation
     * Exemple: "Vous avez été invité à une session de Tennis mardi 5 août de 10h30 à 12h30"
     */
    /**
     * Libellé d'un créneau proposé : "mardi 27 octobre à 19h00"
     */
    public static function formatSlotLabel(array $slot): string
    {
        return self::formatDate($slot['date']) . ' à ' . self::formatTime($slot['startTime']);
    }

    /**
     * Message d'invitation adapté à la session : date fixée, ou créneau encore à définir.
     */
    public static function generateSessionInvitationMessage(\App\Entities\SportSession $session): string
    {
        if (!$session->isScheduled()) {
            $sportName = self::getSportName($session->getSport());
            $count = count($session->getSlotProposals());
            $slots = $count > 1 ? "{$count} créneaux proposés" : '1 créneau proposé';
            return "Vous avez été invité à une session de {$sportName}, date à définir ({$slots}) : indiquez vos disponibilités";
        }

        return self::generateInvitationMessage($session->getSport(), $session->getDate(), $session->getStartTime(), $session->getEndTime());
    }

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
     * Génère un titre de notification push pour un commentaire avec date et prénom
     * Exemple: "Golf le mardi 21 - Jean"
     */
    public static function generatePushCommentTitleWithDate(string $sport, string $date, string $firstName): string
    {
        $sportName = self::getSportName($sport);
        $formattedDate = self::formatDate($date);
        return "{$sportName} {$formattedDate} - {$firstName}";
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
     * Génère un message de notification push pour un commentaire (version courte avec contenu)
     * Exemple: "Super session aujourd'hui !"
     */
    public static function generatePushCommentMessageShort(string $comment): string
    {
        return $comment;
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

    /**
     * Génère un titre de notification push pour un rappel 24h avant
     * Exemple: "⏰ Rappel Tennis"
     */
    public static function generateReminder24hTitle(string $sport): string
    {
        $sportName = self::getSportName($sport);
        return "⏰ Rappel {$sportName}";
    }

    /**
     * Génère un message de notification pour un rappel 24h avant
     * Exemple: "Votre session de Tennis commence demain dimanche 30 novembre de 17h à 19h"
     */
    public static function generateReminder24hMessage(string $sport, string $date, string $startTime, ?string $endTime = null): string
    {
        $sportName = self::getSportName($sport);
        $formattedDate = self::formatDate($date);
        $formattedStartTime = self::formatTime($startTime);

        // Vérifier si c'est vraiment demain (en Europe/Paris)
        $sessionDate = new DateTime($date, new \DateTimeZone('Europe/Paris'));
        $tomorrow = new DateTime('tomorrow', new \DateTimeZone('Europe/Paris'));
        $isTomorrow = $sessionDate->format('Y-m-d') === $tomorrow->format('Y-m-d');

        if ($endTime) {
            $formattedEndTime = self::formatTime($endTime);
            if ($isTomorrow) {
                return "Votre session de {$sportName} commence demain {$formattedDate} de {$formattedStartTime} à {$formattedEndTime}";
            } else {
                return "Votre session de {$sportName} commence {$formattedDate} de {$formattedStartTime} à {$formattedEndTime}";
            }
        } else {
            if ($isTomorrow) {
                return "Votre session de {$sportName} commence demain {$formattedDate} à {$formattedStartTime}";
            } else {
                return "Votre session de {$sportName} commence {$formattedDate} à {$formattedStartTime}";
            }
        }
    }

    /**
     * Génère un titre de notification push pour un rappel 1h avant
     * Exemple: "⏰ Rappel Tennis"
     */
    public static function generateReminder1hTitle(string $sport): string
    {
        $sportName = self::getSportName($sport);
        return "⏰ Rappel {$sportName}";
    }

    /**
     * Génère un message de notification pour un rappel 1h avant
     * Exemple: "Votre session de Football commence dans 1 heure de 18h à 20h"
     */
    public static function generateReminder1hMessage(string $sport, string $date, string $startTime, ?string $endTime = null): string
    {
        $sportName = self::getSportName($sport);
        $formattedStartTime = self::formatTime($startTime);

        if ($endTime) {
            $formattedEndTime = self::formatTime($endTime);
            return "Votre session de {$sportName} commence dans 1 heure de {$formattedStartTime} à {$formattedEndTime}";
        } else {
            return "Votre session de {$sportName} commence dans 1 heure à {$formattedStartTime}";
        }
    }

    /**
     * Génère un titre de notification push pour un rappel au démarrage
     * Exemple: "🏃‍♂️ Session Tennis"
     */
    public static function generateReminderStartTitle(string $sport): string
    {
        $sportName = self::getSportName($sport);
        return "🏃‍♂️ Session {$sportName}";
    }

    /**
     * Génère un message de notification pour un rappel au démarrage
     * Exemple: "Votre session de Basketball commence maintenant !"
     */
    public static function generateReminderStartMessage(string $sport): string
    {
        $sportName = self::getSportName($sport);
        return "Votre session de {$sportName} commence maintenant !";
    }
}
