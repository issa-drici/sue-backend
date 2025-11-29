<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSession\ChangeSessionOrganizerUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChangeSessionOrganizerAction extends Controller
{
    public function __construct(
        private ChangeSessionOrganizerUseCase $changeSessionOrganizerUseCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $data = $request->validate([
                'newOrganizerId' => 'required|string|uuid',
            ]);

            $userId = $request->user()->id;
            $session = $this->changeSessionOrganizerUseCase->execute($id, $data['newOrganizerId'], $userId);

            return response()->json([
                'success' => true,
                'data' => $session->toArray(),
                'message' => 'Organisateur changé avec succès',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Données invalides',
                    'details' => $e->errors(),
                ],
            ], 400);

        } catch (\Exception $e) {
            $statusCode = 500;
            $errorCode = 'INTERNAL_ERROR';

            if (str_contains($e->getMessage(), 'Session non trouvée')) {
                $statusCode = 404;
                $errorCode = 'SESSION_NOT_FOUND';
            } elseif (str_contains($e->getMessage(), 'Utilisateur non trouvé')) {
                $statusCode = 404;
                $errorCode = 'USER_NOT_FOUND';
            } elseif (str_contains($e->getMessage(), 'pas autorisé')) {
                $statusCode = 403;
                $errorCode = 'FORBIDDEN';
            } elseif (str_contains($e->getMessage(), 'déjà l\'organisateur')) {
                $statusCode = 400;
                $errorCode = 'ALREADY_ORGANIZER';
            }

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $errorCode,
                    'message' => $e->getMessage(),
                ],
            ], $statusCode);
        }
    }
}

