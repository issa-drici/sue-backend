<?php

namespace App\Http\Controllers\Badge;

use App\Http\Controllers\Controller;
use App\Models\UserModel;
use App\Services\BadgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Badges d'un joueur (les siens ou ceux d'un autre, visibles par tous).
 * "seen" n'est renvoyé que pour soi : il sert à afficher la célébration une seule fois.
 */
class GetUserBadgesAction extends Controller
{
    public function __construct(private BadgeService $badgeService) {}

    public function __invoke(Request $request, ?string $id = null): JsonResponse
    {
        $userId = $id ?? $request->user()->id;

        if (!UserModel::whereKey($userId)->exists()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'USER_NOT_FOUND', 'message' => 'Utilisateur non trouvé'],
            ], 404);
        }

        $badges = $this->badgeService->getBadges($userId);
        if ($userId !== $request->user()->id) {
            $badges = array_map(fn ($badge) => array_diff_key($badge, ['seen' => true]), $badges);
        }

        return response()->json(['success' => true, 'data' => $badges]);
    }
}
