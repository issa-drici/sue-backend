<?php

namespace App\Exceptions;

use Exception;

/**
 * Un code a déjà été envoyé récemment : il faut attendre avant d'en redemander un.
 */
class OtpResendTooSoonException extends Exception
{
    public function __construct(
        public readonly int $secondsRemaining
    ) {
        parent::__construct("Veuillez patienter {$secondsRemaining}s avant de redemander un code.");
    }
}
