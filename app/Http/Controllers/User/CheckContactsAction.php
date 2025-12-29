<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\UseCases\User\CheckContactsUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CheckContactsAction extends Controller
{
    public function __construct(
        private CheckContactsUseCase $checkContactsUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'phoneNumbers' => ['required', 'array', 'min:1', 'max:2000'],
                'phoneNumbers.*' => ['required', 'string', 'max:20'],
            ], [
                'phoneNumbers.required' => 'Le champ phoneNumbers est obligatoire.',
                'phoneNumbers.array' => 'Le champ phoneNumbers doit être un tableau.',
                'phoneNumbers.min' => 'Au moins un numéro de téléphone est requis.',
                'phoneNumbers.max' => 'Maximum 100 numéros de téléphone autorisés.',
                'phoneNumbers.*.required' => 'Chaque numéro de téléphone est obligatoire.',
                'phoneNumbers.*.string' => 'Chaque numéro de téléphone doit être une chaîne de caractères.',
            ]);

            $currentUserId = $request->user()->id;
            $phoneNumbers = $request->input('phoneNumbers', []);

            $result = $this->checkContactsUseCase->execute($phoneNumbers, $currentUserId);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
