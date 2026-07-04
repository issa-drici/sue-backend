<?php

namespace App\Exceptions;

use Exception;

/**
 * Levée lorsqu'un utilisateur tente de rejoindre une session ayant déjà
 * atteint sa limite de participants.
 */
class SessionFullException extends Exception
{
    public function __construct(int $maxParticipants)
    {
        parent::__construct("Cette session est complète ({$maxParticipants} participants maximum)");
    }
}
