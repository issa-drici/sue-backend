<?php

namespace App\Exceptions;

use Exception;

/**
 * Règle métier non respectée à la création / modification d'une session
 * (créneau en double ou déjà passé, session passée…) : erreur client (422), pas une erreur serveur.
 */
class InvalidSessionDataException extends Exception
{
}
