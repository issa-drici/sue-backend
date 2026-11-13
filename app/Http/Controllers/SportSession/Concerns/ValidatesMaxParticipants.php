<?php

namespace App\Http\Controllers\SportSession\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Nombre maximum de joueurs d'une session : null = illimité, sinon entre 2 et 100
 * (une session se joue au moins à deux). Erreur 422 avec un message clair.
 */
trait ValidatesMaxParticipants
{
    protected function maxParticipantsError(Request $request): ?JsonResponse
    {
        if (!$request->has('maxParticipants') || $request->input('maxParticipants') === null) {
            return null;
        }

        $validator = Validator::make(
            ['maxParticipants' => $request->input('maxParticipants')],
            ['maxParticipants' => 'integer|min:2|max:100'],
            [
                'maxParticipants.integer' => 'Le nombre de joueurs doit être un nombre entier.',
                'maxParticipants.min' => 'Il faut au moins 2 joueurs dans une session.',
                'maxParticipants.max' => '100 joueurs maximum par session.',
            ]
        );

        if (!$validator->fails()) {
            return null;
        }

        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first('maxParticipants'),
                'details' => $validator->errors()->toArray(),
            ],
        ], 422);
    }
}
