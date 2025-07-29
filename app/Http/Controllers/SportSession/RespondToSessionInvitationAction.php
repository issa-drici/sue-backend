<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSession\RespondToSessionInvitationUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RespondToSessionInvitationAction extends Controller
{
    public function __construct(
        private RespondToSessionInvitationUseCase $respondToSessionInvitationUseCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $data = $request->validate([
                'response' => 'required|in:accept,decline',
            ]);

            $userId = $request->user()->id;
            $response = $data['response'];

            $session = $this->respondToSessionInvitationUseCase->execute($id, $userId, $response);

            $message = $response === 'accept' ? 'Invitation acceptée' : 'Invitation déclinée';

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $session->getId(),
                    'sport' => $session->getSport(),
                    'participants' => $session->getParticipants(),
                ],
                'message' => $message,
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
            } elseif (str_contains($e->getMessage(), 'pas invité')) {
                $statusCode = 403;
                $errorCode = 'FORBIDDEN';
            } elseif (str_contains($e->getMessage(), 'Réponse invalide')) {
                $statusCode = 400;
                $errorCode = 'INVALID_RESPONSE';
            } elseif (str_contains($e->getMessage(), 'Impossible d\'accepter l\'invitation')) {
                $statusCode = 400;
                $errorCode = 'PARTICIPANT_LIMIT_REACHED';
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
