<?php

namespace App\Http\Controllers\SportSession;

use App\Http\Controllers\Controller;
use App\UseCases\SportSession\AddSessionCommentUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AddSessionCommentAction extends Controller
{
    public function __construct(
        private AddSessionCommentUseCase $addSessionCommentUseCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $data = $request->validate([
                'content' => 'required|string|min:1|max:500',
            ]);

            $userId = $request->user()->id;
            $content = $data['content'];

            $comment = $this->addSessionCommentUseCase->execute($id, $userId, $content);

            return response()->json([
                'success' => true,
                'data' => $comment,
                'message' => 'Commentaire ajouté',
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
            $statusCode = 500;
            $errorCode = 'INTERNAL_ERROR';

            if (str_contains($e->getMessage(), 'Session non trouvée')) {
                $statusCode = 404;
                $errorCode = 'SESSION_NOT_FOUND';
            } elseif (str_contains($e->getMessage(), 'pas autorisé')) {
                $statusCode = 403;
                $errorCode = 'FORBIDDEN';
            }

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $errorCode,
                    'message' => $e->getMessage(),
                ],
            ], $statusCode);
        }
    }
}
