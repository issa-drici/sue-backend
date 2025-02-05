<?php

namespace App\Http\Controllers\Stats;

use App\Http\Controllers\Controller;
use App\UseCases\Stats\FindUserStatsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FindUserStatsAction extends Controller
{
    public function __construct(
        private FindUserStatsUseCase $useCase
    ) {}

    /**
     * Récupère les statistiques de l'utilisateur sur une période donnée
     * 
     * @param Request $request La requête HTTP contenant les paramètres de filtrage
     * 
     * Paramètres requis :
     * - startDate (YYYY-MM-DD) : Date de début de la période
     * - endDate (YYYY-MM-DD) : Date de fin de la période
     * - period (string) : Période d'analyse ('day'|'week'|'month')
     * 
     * Format de réponse :
     * {
     *   "videos_completed": int,
     *   "total_xp": int,
     *   "total_training_time": int (en secondes),
     *   "most_trained_exercise": {
     *     "id": int,
     *     "name": string
     *   },
     *   "overall_xp": [ // uniquement pour day et week
     *     {
     *       "day": string, // Format court (Mon, Tue, etc.)
     *       "xp": int
     *     }
     *   ]
     * }
     * 
     * Notes :
     * - Pour period=day : retourner les stats détaillées du jour avec overall_xp pour ce jour
     * - Pour period=week : retourner les stats avec overall_xp pour chaque jour de la semaine
     * - Pour period=month : retourner uniquement les totaux du mois (pas de overall_xp)
     * 
     * TODO:
     * - Valider les paramètres startDate et endDate
     * - Calculer pour la période :
     *   - Total XP gagné
     *   - Nombre de vidéos complétées
     *   - Temps total d'entraînement
     *   - Exercice le plus pratiqué
     * - Générer overall_xp selon la période demandée
     * - Gérer les cas d'erreur de paramètres invalides
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date_format:Y-m-d',
                'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
                'period' => 'required|in:day,week,month'
            ]);

            $result = $this->useCase->execute(
                $validated['start_date'],
                $validated['end_date'],
                $validated['period']
            );
            
            return response()->json($result);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
} 