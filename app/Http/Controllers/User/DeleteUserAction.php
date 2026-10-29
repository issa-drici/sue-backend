<?php

namespace App\Http\Controllers\User;

use App\Exceptions\InvalidOtpException;
use App\Http\Controllers\Controller;
use App\Services\PhoneOtpService;
use App\UseCases\User\DeleteUserUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeleteUserAction extends Controller
{
    public function __construct(
        private DeleteUserUseCase $deleteUserUseCase,
        private PhoneOtpService $otpService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        // Suppression confirmée par le code SMS reçu sur le numéro du compte (POST /users/phone/send-otp)
        if ($user->phone) {
            $code = (string) $request->input('code', '');
            if (!preg_match('/^\d{6}$/', $code)) {
                return response()->json(['message' => 'Saisis le code reçu par SMS pour supprimer ton compte.'], 422);
            }
            try {
                $this->otpService->verifyCode($user->phone, $code);
            } catch (InvalidOtpException $e) {
                return response()->json(['message' => 'Code invalide ou expiré.'], 400);
            }
        }

        $result = $this->deleteUserUseCase->execute($user->id);

        if (!$result['success']) {
            $statusCode = match($result['error']['code']) {
                'USER_NOT_FOUND' => 404,
                default => 400
            };

            return response()->json($result, $statusCode);
        }

        if ($user->phone) {
            $this->otpService->consume($user->phone);
        }

        return response()->json($result);
    }
}
