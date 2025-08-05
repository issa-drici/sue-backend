<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            $user = UserModel::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'The provided credentials are incorrect',
                    'errors' => [
                        'email' => ['Email or password is incorrect']
                    ]
                ], 422);
            }

            $token = $user->createToken($request->device_name)->plainTextToken;

            return response()->json([
                'message' => 'Connexion réussie',
                'token' => $token,
                'user' => $user->only('id', 'email', 'firstname', 'lastname', 'role'),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred during login',
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
                    'message' => 'Unauthorized user'
                ], 401);
            }

            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Successfully logged out'
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'message' => 'An error occurred during logout',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
