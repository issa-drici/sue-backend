<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // L'app s'authentifie avec son jeton Sanctum (pas de session web) : POST /broadcasting/auth
        Broadcast::routes(['middleware' => ['auth:sanctum']]);

        require base_path('routes/channels.php');
    }
}
