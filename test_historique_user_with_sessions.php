<?php

// Test pour vérifier l'historique avec l'utilisateur qui a des sessions
require_once 'vendor/autoload.php';

// Initialiser Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Repositories\SportSession\SportSessionRepository;
use App\Models\UserModel;

echo "=== TEST HISTORIQUE UTILISATEUR AVEC SESSIONS ===\n\n";

// 1. Trouver l'utilisateur avec des sessions
echo "🔍 RECHERCHE UTILISATEUR AVEC SESSIONS\n";
echo "=======================================\n";

$user = UserModel::where('email', 'dricimoussa76@gmail.com')->first();
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

    $today = date('Y-m-d');
    $hasTodaySession = false;
    foreach ($sessions->items() as $session) {
        $date = $session->getDate();
        $location = $session->getLocation();
        echo "   - Session: $date - $location\n";

        if ($date === $today) {
            $hasTodaySession = true;
        }
    }

    // 4. Tester findMySessions avec filtre past_sessions
    echo "\n📋 TEST FINDMY SESSIONS AVEC FILTRE PAST_SESSIONS\n";
    echo "==================================================\n";

    $pastSessions = $repository->findMySessions($user->id, ['past_sessions' => true], 1, 20);
    echo "   Nombre de sessions passées: " . $pastSessions->total() . "\n";

    $hasTodaySessionInPast = false;
    foreach ($pastSessions->items() as $session) {
        $date = $session->getDate();
        $location = $session->getLocation();
        echo "   - Session passée: $date - $location\n";

        if ($date === $today) {
            $hasTodaySessionInPast = true;
        }
    }

    echo "\n📊 ANALYSE:\n";
    echo "   " . ($hasTodaySession ? "✅" : "❌") . " Session d'aujourd'hui dans toutes les sessions: " . ($hasTodaySession ? "OUI" : "NON") . "\n";
    echo "   " . ($hasTodaySessionInPast ? "❌" : "✅") . " Session d'aujourd'hui dans les sessions passées: " . ($hasTodaySessionInPast ? "OUI (ERREUR)" : "NON (CORRECT)") . "\n";

    // 5. Vérifier les dates des sessions en base
    echo "\n🔍 VÉRIFICATION DATES EN BASE\n";
    echo "==============================\n";

    $participations = \App\Models\SportSessionParticipantModel::with(['session'])
        ->where('user_id', $user->id)
        ->whereNotIn('status', ['declined'])
        ->get();

    echo "   Sessions de l'utilisateur en base:\n";
    foreach ($participations as $participation) {
        $date = $participation->session->date->format('Y-m-d');
        $location = $participation->session->location;
        $status = $participation->status;
        echo "   - $date - $location (status: $status)\n";
    }

    // 6. Vérifier la requête SQL pour les sessions passées
    echo "\n🔍 REQUÊTE SQL SESSIONS PASSÉES\n";
    echo "================================\n";

    $query = \App\Models\SportSessionModel::with(['organizer', 'participants.user', 'comments.user'])
        ->whereHas('participants', function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->whereNotIn('status', ['declined']);
        })
        ->where('date', '<', now()->format('Y-m-d'));

    echo "   Requête SQL:\n";
    echo "   " . $query->toSql() . "\n";
    echo "   Paramètres: " . json_encode($query->getBindings()) . "\n";

    $manualResults = $query->get();
    echo "   Nombre de résultats: " . $manualResults->count() . "\n";

    foreach ($manualResults as $session) {
        $date = $session->date->format('Y-m-d');
        $location = $session->location;
        echo "   - Résultat: $date - $location\n";
    }

} else {
    echo "   ❌ Utilisateur dricimoussa76@gmail.com non trouvé\n";
}

echo "\n=== TEST TERMINÉ ===\n";
