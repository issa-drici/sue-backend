<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSession\CreateSportSessionUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CreateSportSessionAction extends Controller
{
    public function __construct(
        private CreateSportSessionUseCase $createSportSessionUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'sport' => 'required|in:' . \App\Services\SportService::getValidationRule(),
                'date' => 'required|date_format:Y-m-d|after_or_equal:today',
                'startTime' => 'required|date_format:H:i',
                'endTime' => 'required|date_format:H:i',
                'location' => 'required|string|max:255',
                'maxParticipants' => 'nullable|integer|min:1|max:100',
                'pricePerPerson' => 'nullable|numeric|min:0',
                'participantIds' => 'nullable|array',
                'participantIds.*' => 'required|string|uuid'
            ]);

            // Validation personnalisée pour s'assurer que endTime > startTime
            if (strtotime($data['endTime']) <= strtotime($data['startTime'])) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Données invalides',
                        'details' => [
                            'endTime' => ['L\'heure de fin doit être après l\'heure de début']
                        ],
                    ],
                ], 400);
            }

            // Ajouter l'organisateur (utilisateur connecté)
            $data['organizer_id'] = $request->user()->id;

            $session = $this->createSportSessionUseCase->execute($data);

            return response()->json([
                'success' => true,
                'data' => $session->toArray(),
                'message' => 'Session créée avec succès',
            ], 201);

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
