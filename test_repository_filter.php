<?php

// Test pour vérifier directement la logique de filtrage dans le repository
require_once 'vendor/autoload.php';

// Initialiser Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Repositories\SportSession\SportSessionRepository;
use App\Models\UserModel;

echo "=== TEST LOGIQUE FILTRAGE REPOSITORY ===\n\n";

// 1. Trouver un utilisateur existant
echo "🔍 RECHERCHE UTILISATEUR\n";
echo "========================\n";

$user = UserModel::first();
if ($user) {
    echo "   ✅ Utilisateur trouvé: " . $user->email . "\n";
    echo "   User ID: " . $user->id . "\n\n";

    // 2. Créer une instance du repository
    $repository = new SportSessionRepository();

    // 3. Tester findMySessions sans filtre
    echo "📋 TEST FINDMY SESSIONS SANS FILTRE\n";
    echo "====================================\n";

    $sessions = $repository->findMySessions($user->id, [], 1, 20);
    echo "   Nombre total de sessions: " . $sessions->total() . "\n";

    foreach ($sessions->items() as $session) {
        $date = $session->getDate();
        $location = $session->getLocation();
        echo "   - Session: $date - $location\n";
    }

    // 4. Tester findMySessions avec filtre past_sessions
    echo "\n📋 TEST FINDMY SESSIONS AVEC FILTRE PAST_SESSIONS\n";
    echo "==================================================\n";

    $pastSessions = $repository->findMySessions($user->id, ['past_sessions' => true], 1, 20);
    echo "   Nombre de sessions passées: " . $pastSessions->total() . "\n";

    $today = date('Y-m-d');
    $hasTodaySession = false;
    foreach ($pastSessions->items() as $session) {
        $date = $session->getDate();
        $location = $session->getLocation();
        echo "   - Session passée: $date - $location\n";

        if ($date === $today) {
            $hasTodaySession = true;
        }
    }

    echo "\n📊 ANALYSE:\n";
    echo "   " . ($hasTodaySession ? "❌" : "✅") . " Session d'aujourd'hui dans les sessions passées: " . ($hasTodaySession ? "OUI (ERREUR)" : "NON (CORRECT)") . "\n";

    // 5. Vérifier la requête SQL générée
    echo "\n🔍 VÉRIFICATION REQUÊTE SQL\n";
    echo "============================\n";

    // Simuler la requête manuellement
    $query = \App\Models\SportSessionModel::with(['organizer', 'participants.user', 'comments.user'])
        ->whereHas('participants', function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->whereNotIn('status', ['declined']);
        })
        ->where('date', '<', now()->format('Y-m-d'));

    echo "   Requête SQL générée:\n";
    echo "   " . $query->toSql() . "\n";
    echo "   Paramètres: " . json_encode($query->getBindings()) . "\n";

    $manualResults = $query->get();
    echo "   Nombre de résultats manuels: " . $manualResults->count() . "\n";

    foreach ($manualResults as $session) {
        $date = $session->date->format('Y-m-d');
        $location = $session->location;
        echo "   - Résultat manuel: $date - $location\n";
    }

} else {
    echo "   ❌ Aucun utilisateur trouvé dans la base de données\n";
}

echo "\n=== TEST TERMINÉ ===\n";
