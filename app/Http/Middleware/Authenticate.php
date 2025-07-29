<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
        protected function redirectTo(Request $request): ?string
    {
        // Pour les requêtes API, ne pas rediriger, retourner une erreur JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }

        // Pour les requêtes web, rediriger vers une page de login si elle existe
        try {
            return route('login');
        } catch (\Exception $e) {
            // Si la route login n'existe pas, retourner null
            return null;
        }
    }

    protected function unauthenticated($request, array $guards)
    {
        // Pour les requêtes API, retourner une erreur JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            abort(response()->json([
                'message' => 'Unauthenticated.',
            ], 401));
        }

        // Pour les requêtes web, rediriger
        $this->redirectTo($request);
    }
}
