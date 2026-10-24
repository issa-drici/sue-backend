<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\Exceptions\InvalidShareLinkException;
use App\Exceptions\SessionFullException;
use App\UseCases\SportSession\JoinSessionByShareTokenUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Endpoint AUTHENTIFIÉ : l'utilisateur connecté rejoint une session à partir
 * d'un token de partage. Renvoie la session complète pour permettre à l'app de
 * naviguer directement vers l'écran de session.
 */
class JoinSessionByShareTokenAction extends Controller
{
    public function __construct(
        private JoinSessionByShareTokenUseCase $joinSessionByShareTokenUseCase,
        private \App\UseCases\SportSession\RespondToSessionInvitationUseCase $respondToSessionInvitationUseCase,
        private \App\Repositories\SportSession\SportSessionRepositoryInterface $sportSessionRepository
    ) {}

    public function __invoke(Request $request, string $token): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            // Identité de celui qui a partagé le lien (facultatif) → devient l'ami créé
            $from = $request->input('from');

            // "decline" : réponse "Je ne peux pas" depuis la page web (sans l'app)
            if ($request->input('response') === 'decline') {
                $session = $this->declineByShareToken($token, $userId);

                return response()->json([
                    'success' => true,
                    'data' => $session->toArray(),
                    'message' => 'Réponse enregistrée',
                ]);
            }

            $session = $this->joinSessionByShareTokenUseCase->execute($token, $userId, $from);

            return response()->json([
                'success' => true,
                'data' => $session->toArray(),
                'message' => 'Vous avez rejoint la session',
            ]);

        } catch (InvalidShareLinkException $e) {
            Log::info('Share link join failed', [
                'reason' => $e->reason,
                'userId' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_SHARE_LINK',
                    'message' => 'Ce lien de session n\'est plus valide.',
                ],
            ], 404);

        } catch (SessionFullException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SESSION_FULL',
                    'message' => $e->getMessage(),
                ],
            ], 409);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Ajoute l'utilisateur comme invité s'il ne l'est pas, puis décline (notification à l'organisateur
     * et message système, comme depuis l'app).
     */
    private function declineByShareToken(string $token, string $userId): \App\Entities\SportSession
    {
        $session = $this->sportSessionRepository->findByShareToken($token);
        if (!$session || !$session->isShareLinkActive()) {
            throw new InvalidShareLinkException('token_not_found');
        }
        if ($session->isOrganizer($userId)) {
            return $session;
        }
        if (!$session->isParticipant($userId)) {
            $this->sportSessionRepository->addParticipant($session->getId(), $userId, 'pending');
        }
        return $this->respondToSessionInvitationUseCase->execute($session->getId(), $userId, 'decline');
    }
}
