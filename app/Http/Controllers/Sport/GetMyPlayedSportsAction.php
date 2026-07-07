<?php

namespace App\Http\Controllers\Sport;

use App\Http\Controllers\Controller;
use App\Models\SportSessionParticipantModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sports auxquels l'utilisateur a participé (statut "accepted", sessions non annulées),
 * triés du plus pratiqué au moins pratiqué. Utilisé pour les accès rapides (badges)
 * de l'écran de création de session.
 */
class GetMyPlayedSportsAction extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $rows = SportSessionParticipantModel::query()
            ->join('sport_sessions', 'sport_session_participants.session_id', '=', 'sport_sessions.id')
            ->where('sport_session_participants.user_id', $userId)
            ->where('sport_session_participants.status', 'accepted')
            ->where('sport_sessions.status', '!=', 'cancelled')
            ->groupBy('sport_sessions.sport')
            ->orderByRaw('COUNT(*) DESC')
            ->selectRaw('sport_sessions.sport as sport, COUNT(*) as sessions_count')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rows->map(fn ($r) => [
                'sport' => $r->sport,
                'count' => (int) $r->sessions_count,
            ])->values(),
        ]);
    }
}
