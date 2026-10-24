<?php

namespace App\Http\Controllers\Badge;

use App\Http\Controllers\Controller;
use App\Services\BadgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Célébration affichée : ces badges ne seront plus montrés comme nouveaux */
class MarkBadgesSeenAction extends Controller
{
    public function __construct(private BadgeService $badgeService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'badgeIds' => 'required|array',
            'badgeIds.*' => 'required|string|max:40',
        ]);

        $this->badgeService->markSeen($request->user()->id, $data['badgeIds']);

        return response()->json(['success' => true]);
    }
}
