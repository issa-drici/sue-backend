<?php

namespace App\Exceptions;

use Exception;

/**
 * Code OTP invalide, expiré, ou trop de tentatives. Message volontairement
 * générique côté client (la raison précise est journalisée si besoin).
 */
class InvalidOtpException extends Exception
{
    public function __construct(
        public readonly string $reason = 'invalid_otp'
    ) {
        parent::__construct('Code invalide ou expiré.');
    }
}
