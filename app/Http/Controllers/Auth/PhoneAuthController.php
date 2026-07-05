<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\InvalidOtpException;
use App\Exceptions\OtpResendTooSoonException;
use App\Exceptions\PhoneNotVerifiedException;
use App\Http\Controllers\Controller;
use App\Models\UserModel;
use App\Services\PhoneOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Authentification par numéro de téléphone + code SMS (OTP).
 *
 *  POST /api/auth/phone/send-otp  : envoie un code à 6 chiffres par SMS
 *  POST /api/auth/phone/verify    : vérifie le code ; connecte si le numéro est déjà inscrit
 *  POST /api/auth/phone/register  : crée le profil (prénom/nom) après une vérification réussie
 */
class PhoneAuthController extends Controller
{
    public function __construct(
        private PhoneOtpService $otpService
    ) {}

    public function sendOtp(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'phone' => ['required', 'string'],
            ]);

            $phone = $this->normalizePhone($data['phone']);
            $this->assertValidPhone($phone);

            $this->otpService->sendCode($phone);

            return response()->json([
                'success' => true,
                'message' => 'Verification code sent.',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Données invalides',
                'errors' => $e->errors(),
            ], 422);
        } catch (OtpResendTooSoonException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'retry_after' => $e->secondsRemaining,
            ], 429);
        } catch (\Throwable $e) {
            Log::error('Erreur envoi OTP', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Impossible d\'envoyer le code de vérification.',
            ], 500);
        }
    }

    public function verify(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'phone' => ['required', 'string'],
                'code' => ['required', 'string', 'regex:/^\d{6}$/'],
                'device_name' => ['sometimes', 'string'],
            ]);

            $phone = $this->normalizePhone($data['phone']);
            $this->assertValidPhone($phone);

            $this->otpService->verifyCode($phone, $data['code']);

            $user = UserModel::where('phone', $phone)->first();

            // Numéro non inscrit → l'app redirige vers le formulaire prénom/nom
            if (!$user) {
                return response()->json([
                    'isRegistered' => false,
                    'message' => 'Profile registration required.',
                ], 200);
            }

            // Numéro déjà inscrit → connexion directe
            $this->otpService->consume($phone);

            return response()->json([
                'isRegistered' => true,
                'token' => $user->createToken($data['device_name'] ?? 'SUE Mobile App')->plainTextToken,
                'user' => $user->only('id', 'phone', 'firstname', 'lastname', 'email', 'role'),
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Données invalides',
                'errors' => $e->errors(),
            ], 422);
        } catch (InvalidOtpException $e) {
            return response()->json([
                'message' => 'Code invalide ou expiré.',
            ], 400);
        } catch (\Throwable $e) {
            Log::error('Erreur vérification OTP', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Une erreur est survenue lors de la vérification.',
            ], 500);
        }
    }

    public function register(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'phone' => ['required', 'string'],
                'firstname' => ['required', 'string', 'max:100'],
                'lastname' => ['required', 'string', 'max:100'],
                'device_name' => ['sometimes', 'string'],
            ]);

            $phone = $this->normalizePhone($data['phone']);
            $this->assertValidPhone($phone);

            // Sécurité : le numéro doit avoir été vérifié récemment par OTP
            $this->otpService->assertRecentlyVerified($phone);

            $deviceName = $data['device_name'] ?? 'SUE Mobile App';

            // Si le compte existe déjà (course entre verify et register), on connecte simplement.
            $user = UserModel::where('phone', $phone)->first();

            if (!$user) {
                $user = UserModel::create([
                    'firstname' => trim($data['firstname']),
                    'lastname' => $this->formatLastName($data['lastname']),
                    'phone' => $phone,
                    'email' => null,
                    'password' => null,
                    'role' => 'player',
                ]);
            }

            $this->otpService->consume($phone);

            return response()->json([
                'token' => $user->createToken($deviceName)->plainTextToken,
                'user' => $user->only('id', 'phone', 'firstname', 'lastname', 'email', 'role'),
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Données invalides',
                'errors' => $e->errors(),
            ], 422);
        } catch (PhoneNotVerifiedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 401);
        } catch (\Throwable $e) {
            Log::error('Erreur inscription téléphone', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Une erreur est survenue lors de la création du profil.',
            ], 500);
        }
    }

    /**
     * Normalise un numéro français vers le format E.164 (+33XXXXXXXXX).
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^\d+]/', '', trim($phone));

        // 0XXXXXXXXX → +33XXXXXXXXX
        if (preg_match('/^0(\d{9})$/', $phone, $m)) {
            return '+33' . $m[1];
        }
        // 33XXXXXXXXX → +33XXXXXXXXX
        if (preg_match('/^33(\d{9})$/', $phone, $m)) {
            return '+33' . $m[1];
        }
        // XXXXXXXXX (9 chiffres, commence par 6/7) → +33XXXXXXXXX
        if (preg_match('/^([67]\d{8})$/', $phone, $m)) {
            return '+33' . $m[1];
        }

        return $phone;
    }

    /**
     * @throws ValidationException si le numéro n'est pas au format E.164 valide
     */
    private function assertValidPhone(string $phone): void
    {
        if (!preg_match('/^\+[1-9]\d{7,14}$/', $phone)) {
            throw ValidationException::withMessages([
                'phone' => ['Le format du numéro de téléphone est invalide.'],
            ]);
        }
    }

    private function formatLastName(string $lastname): string
    {
        $lastname = mb_strtolower(trim($lastname), 'UTF-8');

        return preg_replace_callback(
            '/(^|[\s\-\'])(\p{L})/u',
            fn (array $matches) => $matches[1] . mb_strtoupper($matches[2], 'UTF-8'),
            $lastname
        );
    }
}
