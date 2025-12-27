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
    private const VERIFICATION_CODE = 'PRO-8X24-UT'; // Code statique pour la démonstration

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
                'firstname' => ['required', 'string', 'max:100'],
                'lastname' => ['required', 'string', 'max:100'],
                'phone' => ['required', 'string', 'max:20', 'unique:users', 'regex:/^\+?[1-9]\d{1,14}$/'],
                'device_name' => ['required', 'string'],
            ], [
                'phone.required' => 'Le numéro de téléphone est obligatoire.',
                'phone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
                'phone.regex' => 'Le format du numéro de téléphone est invalide.',
            ]);

            $user = UserModel::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'phone' => $request->phone,
                'role' => 'player',
            ]);

            event(new Registered($user));
            Auth::login($user);

            return response()->json([
                'token' => $user->createToken($request->device_name)->plainTextToken,
                'user' => $user
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => "Registration failed",
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => "An error occurred during registration",
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
