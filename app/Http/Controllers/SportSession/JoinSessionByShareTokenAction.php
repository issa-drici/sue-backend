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
        private JoinSessionByShareTokenUseCase $joinSessionByShareTokenUseCase
    ) {}

    public function __invoke(Request $request, string $token): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            $session = $this->joinSessionByShareTokenUseCase->execute($token, $userId);

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
}
