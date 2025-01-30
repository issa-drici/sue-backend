<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserModel;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', Rules\Password::defaults()],
                'full_name' => ['required', 'string', 'max:255'],
                'device_name' => ['required', 'string'],
            ]);
            
            $user = UserModel::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'full_name' => $request->full_name,
                'role' => 'player', // valeur par défaut
            ]);

            event(new Registered($user));
            Auth::login($user);

            return response()->json([
                'token' => $user->createToken($request->device_name)->plainTextToken,
                'user' => $user
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'L\'inscription a échoué',
                'errors' => [
                    'email' => ['Cet email est déjà utilisé']
                ]
            ], 422);
        }
    }
}
