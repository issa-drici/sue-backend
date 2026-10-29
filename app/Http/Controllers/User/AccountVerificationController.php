<?php

namespace App\Http\Controllers\User;

use App\Exceptions\InvalidOtpException;
use App\Exceptions\OtpResendTooSoonException;
use App\Exceptions\PhoneNotVerifiedException;
use App\Http\Controllers\Controller;
use App\Models\UserModel;
use App\Services\PhoneNumber;
use App\Services\PhoneOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Actions sensibles du compte, validées par un code SMS (le numéro est l'identifiant du compte).
 *
 * Changer de numéro :
 *  POST /api/users/phone/send-otp       : code sur le numéro actuel (sert aussi avant une suppression)
 *  POST /api/users/phone/verify-current : vérifie ce code ; ouvre une fenêtre de 10 min
 *  POST /api/users/phone/new/send-otp   : code sur le nouveau numéro (numéro actuel vérifié avant)
 *  POST /api/users/phone/new/verify     : vérifie ce code et remplace le numéro du compte
 */
class AccountVerificationController extends Controller
{
    public function __construct(
        private PhoneOtpService $otpService
    ) {}

    public function sendCurrentCode(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->phone) {
            return response()->json(['message' => 'Aucun numéro de téléphone sur ce compte.'], 422);
        }

        return $this->send($user->phone);
    }

    public function verifyCurrent(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'regex:/^\d{6}$/']]);
        $user = $request->user();
        if (!$user->phone) {
            return response()->json(['message' => 'Aucun numéro de téléphone sur ce compte.'], 422);
        }

        try {
            $this->otpService->verifyCode($user->phone, $data['code']);
        } catch (InvalidOtpException $e) {
            Log::info('Échec vérification OTP (compte)', ['user_id' => $user->id, 'reason' => $e->reason]);
            return response()->json(['message' => 'Code invalide ou expiré.'], 400);
        }

        return response()->json(['success' => true]);
    }

    public function sendNewCode(Request $request): JsonResponse
    {
        $data = $request->validate(['phone' => ['required', 'string']]);
        $user = $request->user();

        if ($error = $this->assertCurrentVerified($user)) {
            return $error;
        }

        $phone = PhoneNumber::normalize($data['phone']);
        if ($error = $this->assertAvailable($user, $phone)) {
            return $error;
        }

        return $this->send($phone);
    }

    public function confirmNew(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);
        $user = $request->user();

        if ($error = $this->assertCurrentVerified($user)) {
            return $error;
        }

        $phone = PhoneNumber::normalize($data['phone']);
        if ($error = $this->assertAvailable($user, $phone)) {
            return $error;
        }

        try {
            $this->otpService->verifyCode($phone, $data['code']);
        } catch (InvalidOtpException $e) {
            Log::info('Échec vérification OTP (nouveau numéro)', ['user_id' => $user->id, 'reason' => $e->reason]);
            return response()->json(['message' => 'Code invalide ou expiré.'], 400);
        }

        $previousPhone = $user->phone;
        $user->update(['phone' => $phone]);
        if ($previousPhone) {
            $this->otpService->consume($previousPhone);
        }
        $this->otpService->consume($phone);

        return response()->json(['success' => true, 'phone' => $phone]);
    }

    private function send(string $phone): JsonResponse
    {
        try {
            $this->otpService->sendCode($phone);
        } catch (OtpResendTooSoonException $e) {
            return response()->json(['message' => $e->getMessage(), 'retry_after' => $e->secondsRemaining], 429);
        } catch (\Throwable $e) {
            Log::error('Erreur envoi OTP (compte)', ['error' => $e->getMessage()]);
            return response()->json(['message' => "Impossible d'envoyer le code de vérification."], 500);
        }

        return response()->json(['success' => true]);
    }

    /** Le numéro actuel doit avoir été vérifié dans les 10 dernières minutes (comptes sans numéro : rien à vérifier) */
    private function assertCurrentVerified(UserModel $user): ?JsonResponse
    {
        if (!$user->phone) {
            return null;
        }
        try {
            $this->otpService->assertRecentlyVerified($user->phone);
        } catch (PhoneNotVerifiedException $e) {
            return response()->json(['message' => 'Confirme d\'abord ton numéro actuel.'], 401);
        }
        return null;
    }

    private function assertAvailable(UserModel $user, string $phone): ?JsonResponse
    {
        if (!PhoneNumber::isValid($phone)) {
            return response()->json(['message' => 'Le format du numéro de téléphone est invalide.'], 422);
        }
        if ($phone === $user->phone) {
            return response()->json(['message' => "C'est déjà ton numéro."], 422);
        }
        if (UserModel::where('phone', $phone)->where('id', '!=', $user->id)->exists()) {
            return response()->json(['message' => 'Ce numéro est déjà utilisé par un autre compte.'], 409);
        }
        return null;
    }
}
