<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Derrière le proxy de Coolify : lire la vraie IP du client (X-Forwarded-For). Sans ça, toutes
        // les requêtes semblent venir du proxy et les limites (throttle) sont partagées par tout le monde
        $middleware->trustProxies(at: '*');

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'auth' => \App\Http\Middleware\Authenticate::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Erreurs serveur non gérées → alerte Telegram (une fois par erreur et par 30 min)
        $exceptions->report(function (\Throwable $e) {
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface && $e->getStatusCode() < 500) {
                return;
            }
            $where = basename($e->getFile()) . ':' . $e->getLine();
            $url = app()->runningInConsole() ? 'console' : request()->method() . ' ' . request()->path();
            app(\App\Services\TelegramAlertService::class)->sendOnce(
                'exception:' . md5(get_class($e) . $where),
                "💥 Erreur serveur\n{$url}\n" . class_basename($e) . ': ' . \Illuminate\Support\Str::limit($e->getMessage(), 300) . "\n{$where}"
            );
        });
    })->create();
