<?php

namespace App\Exceptions;

use Exception;

/** Erreur métier du chat de session, avec son code HTTP et son code d'erreur pour l'app */
class ChatException extends Exception
{
    public function __construct(string $message, public readonly int $status, public readonly string $errorCode)
    {
        parent::__construct($message);
    }
}
