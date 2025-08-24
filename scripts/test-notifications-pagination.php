<?php

/**
 * Script de diagnostic pour tester la pagination des notifications
 * Usage: php scripts/test-notifications-pagination.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Bootstrap Laravel
$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'auth' => \App\Http\Middleware\Authenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Diagnostic de la pagination des notifications\n";
echo "==============================================\n\n";

// Créer un utilisateur de test
$user = \App\Models\UserModel::factory()->create();
echo "✅ Utilisateur créé: {$user->email}\n";

// Créer 47 notifications
for ($i = 0; $i < 47; $i++) {
    \App\Models\NotificationModel::factory()->create([
        'user_id' => $user->id,
        'type' => 'invitation',
        'title' => "Notification {$i}",
        'message' => "Message {$i}",
    ]);
}
echo "✅ 47 notifications créées\n\n";

// Créer un token
$token = $user->createToken('test-device')->plainTextToken;
echo "✅ Token créé\n\n";

// Simuler une requête HTTP
$request = \Illuminate\Http\Request::create('/api/notifications?page=1&limit=20', 'GET');
$request->headers->set('Authorization', "Bearer {$token}");
$request->headers->set('Accept', 'application/json');

// Exécuter la requête
$response = $app->handle($request);
$content = $response->getContent();
$data = json_decode($content, true);

echo "📊 Résultats de la requête:\n";
echo "Status: {$response->getStatusCode()}\n";
echo "Content-Type: {$response->headers->get('Content-Type')}\n\n";

if ($data) {
    echo "📋 Données de pagination:\n";
    if (isset($data['pagination'])) {
        $pagination = $data['pagination'];
        echo "- Page: {$pagination['page']}\n";
        echo "- Limit: {$pagination['limit']}\n";
        echo "- Total: {$pagination['total']}\n";
        echo "- TotalPages: {$pagination['totalPages']}\n";
        echo "- Nombre d'éléments retournés: " . count($data['data']) . "\n\n";
        
        // Vérifications
        $expectedTotal = 47;
        $expectedTotalPages = ceil(47 / 20);
        $expectedItems = 20;
        
        echo "🔍 Vérifications:\n";
        echo "- Total attendu: {$expectedTotal} | Reçu: {$pagination['total']} | " . 
             ($pagination['total'] === $expectedTotal ? "✅" : "❌") . "\n";
        echo "- TotalPages attendu: {$expectedTotalPages} | Reçu: {$pagination['totalPages']} | " . 
             ($pagination['totalPages'] === $expectedTotalPages ? "✅" : "❌") . "\n";
        echo "- Items attendus: {$expectedItems} | Reçus: " . count($data['data']) . " | " . 
             (count($data['data']) === $expectedItems ? "✅" : "❌") . "\n\n";
    } else {
        echo "❌ Pas de données de pagination trouvées\n";
    }
    
    echo "📄 Réponse complète:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
} else {
    echo "❌ Erreur lors du décodage de la réponse\n";
    echo "Contenu brut: {$content}\n";
}

// Test de la deuxième page
echo "🔄 Test de la deuxième page:\n";
$request2 = \Illuminate\Http\Request::create('/api/notifications?page=2&limit=20', 'GET');
$request2->headers->set('Authorization', "Bearer {$token}");
$request2->headers->set('Accept', 'application/json');

$response2 = $app->handle($request2);
$data2 = json_decode($response2->getContent(), true);

if ($data2 && isset($data2['pagination'])) {
    $pagination2 = $data2['pagination'];
    echo "- Page: {$pagination2['page']}\n";
    echo "- Total: {$pagination2['total']}\n";
    echo "- TotalPages: {$pagination2['totalPages']}\n";
    echo "- Nombre d'éléments retournés: " . count($data2['data']) . "\n";
    echo "- Cohérence avec la première page: " . 
         ($pagination2['total'] === $pagination['total'] && $pagination2['totalPages'] === $pagination['totalPages'] ? "✅" : "❌") . "\n\n";
}

// Nettoyage
$user->tokens()->delete();
$user->delete();
echo "🧹 Nettoyage effectué\n";
echo "✅ Diagnostic terminé\n";
