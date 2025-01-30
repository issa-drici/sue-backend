<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => ['required', 'string', 'email', 'max:255'],
                'password' => ['required', 'string'],
                'device_name' => ['required', 'string'],
            ]);

            $user = UserModel::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Les identifiants fournis sont incorrects',
                    'errors' => [
                        'email' => ['Email ou mot de passe incorrect']
                    ]
                ], 422);
            }

            $token = $user->createToken($request->device_name)->plainTextToken;

            return response()->json([
                'message' => 'Connexion réussie',
                'token' => $token,
                'user' => $user->only('id', 'email', 'full_name', 'role'),
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            
            return response()->json([
                'message' => 'Une erreur est survenue lors de la connexion',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): JsonResponse
    {
        try {
            if (!$request->user()) {
                return response()->json([
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Déconnexion réussie'
            ], 200);

        } catch (\Exception $e) {
            
            return response()->json([
                'message' => 'Une erreur est survenue lors de la déconnexion',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
