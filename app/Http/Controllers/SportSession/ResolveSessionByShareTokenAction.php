<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\Exceptions\InvalidShareLinkException;
use App\UseCases\SportSession\ResolveSessionByShareTokenUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Endpoint PUBLIC (sans authentification) : résout un token de partage vers
 * l'aperçu minimal d'une session, pour la landing web et l'écran de preview
 * de l'app.
 *
 * Réponse volontairement générique en cas d'échec (token inexistant, expiré ou
 * session annulée) afin de ne pas faciliter l'énumération des tokens. La raison
 * précise est journalisée côté serveur uniquement.
 */
class ResolveSessionByShareTokenAction extends Controller
{
    public function __construct(
        private ResolveSessionByShareTokenUseCase $resolveSessionByShareTokenUseCase
    ) {}

    public function __invoke(Request $request, string $token): JsonResponse
    {
        try {
            $session = $this->resolveSessionByShareTokenUseCase->execute($token);

            $preview = $session->toPublicPreview();

            // Si le lien porte ?from={userId} et que cet utilisateur est bien un
            // participant de la session, on expose "l'inviteur" (celui qui a partagé).
            $from = $request->query('from');
            if ($from) {
                $inviter = $session->findParticipantPublicInfo($from);
                if ($inviter) {
                    $preview['inviter'] = $inviter;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $preview,
            ]);

        } catch (InvalidShareLinkException $e) {
            Log::info('Share link resolution failed', [
                'reason' => $e->reason,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_SHARE_LINK',
                    'message' => 'Ce lien de session n\'est plus valide.',
                ],
            ], 404);

        } catch (\Throwable $e) {
            Log::error('Unexpected error resolving share link', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_SHARE_LINK',
                    'message' => 'Ce lien de session n\'est plus valide.',
                ],
            ], 404);
        }
    }
}
