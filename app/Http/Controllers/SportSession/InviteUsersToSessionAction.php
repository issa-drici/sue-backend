<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSession\InviteUsersToSessionUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InviteUsersToSessionAction extends Controller
{
    public function __construct(
        private InviteUsersToSessionUseCase $inviteUsersToSessionUseCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $data = $request->validate([
                'userIds' => 'required|array',
                'userIds.*' => 'required|string|uuid'
            ]);

            $userId = $request->user()->id;
            $userIds = $data['userIds'];

            $result = $this->inviteUsersToSessionUseCase->execute($id, $userId, $userIds);

            if (!$result['success']) {
                $statusCode = match($result['error']['code']) {
                    'SESSION_NOT_FOUND' => 404,
                    'FORBIDDEN' => 403,
                    'USER_NOT_FOUND' => 404,
                    default => 400
                };

                return response()->json($result, $statusCode);
            }

            return response()->json($result, 201);

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
