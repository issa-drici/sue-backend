<?php

namespace App\Services;

use Exception;

/**
 * "Trouver un créneau" : règles communes aux créneaux ajoutés par l'organisateur ou par un invité.
 */
class SlotProposalValidator
{
    public static function validate(array $slot): void
    {
        $date = \DateTime::createFromFormat('Y-m-d', $slot['date'] ?? '');
        $start = \DateTime::createFromFormat('H:i', $slot['startTime'] ?? '');
        $end = \DateTime::createFromFormat('H:i', $slot['endTime'] ?? '');
        if (!$date || !$start || !$end) {
            throw new Exception('Créneau invalide');
        }

        if ($slot['endTime'] === $slot['startTime']) {
            throw new Exception('L\'heure de fin doit être différente de l\'heure de début');
        }

        $startAt = new \DateTime($slot['date'] . ' ' . $slot['startTime'], new \DateTimeZone('Europe/Paris'));
        if ($startAt <= new \DateTime('now', new \DateTimeZone('Europe/Paris'))) {
            throw new Exception('Un créneau proposé est déjà passé');
        }
    }

    /**
     * Deux créneaux qui commencent au même moment font doublon, même si leur fin diffère.
     */
    public static function startKey(array $slot): string
    {
        return "{$slot['date']} {$slot['startTime']}";
    }
}
