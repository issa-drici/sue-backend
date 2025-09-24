<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\SportSession\UpdateSportSessionAction;
use App\Models\UserModel;
use App\Models\SportSessionModel;

// Initialiser Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test de la gestion des champs nullable ===\n\n";

// Créer un utilisateur de test
$user = UserModel::factory()->create([
    'firstname' => 'Test',
    'lastname' => 'User',
    'email' => 'test@example.com',
]);

echo "✅ Utilisateur créé: {$user->firstname} {$user->lastname}\n";

// Créer une session avec maxParticipants et pricePerPerson définis
$session = SportSessionModel::factory()->create([
    'organizer_id' => $user->id,
    'sport' => 'tennis',
    'date' => now()->addDays(7)->format('Y-m-d'),
    'start_time' => '14:00',
    'end_time' => '16:00',
    'location' => 'Tennis Club',
    'max_participants' => 4,
    'price_per_person' => 10.50,
    'status' => 'active',
]);

echo "✅ Session créée avec maxParticipants=4 et pricePerPerson=10.50\n";
echo "   ID: {$session->id}\n";
echo "   Sport: {$session->sport}\n";
echo "   Date: {$session->date}\n";
echo "   Max Participants: {$session->max_participants}\n";
echo "   Price Per Person: {$session->price_per_person}\n\n";

// Test 1: Mettre maxParticipants à null
echo "=== Test 1: Mise à jour de maxParticipants à null ===\n";

$request = Request::create("/api/sessions/{$session->id}", 'PUT', [
    'maxParticipants' => null,
]);

$request->setUserResolver(function () use ($user) {
    return $user;
});

$controller = new UpdateSportSessionAction(
    app(\App\UseCases\SportSession\UpdateSportSessionUseCase::class)
);

try {
    $response = $controller($request, $session->id);
    $responseData = json_decode($response->getContent(), true);
    
    if ($response->getStatusCode() === 200) {
        echo "✅ Succès: maxParticipants mis à null\n";
        
        // Vérifier en base
        $session->refresh();
        echo "   Vérification en base: max_participants = " . ($session->max_participants ?? 'NULL') . "\n";
    } else {
        echo "❌ Erreur: " . $responseData['error']['message'] . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Mettre pricePerPerson à null
echo "=== Test 2: Mise à jour de pricePerPerson à null ===\n";

$request = Request::create("/api/sessions/{$session->id}", 'PUT', [
    'pricePerPerson' => null,
]);

$request->setUserResolver(function () use ($user) {
    return $user;
});

try {
    $response = $controller($request, $session->id);
    $responseData = json_decode($response->getContent(), true);
    
    if ($response->getStatusCode() === 200) {
        echo "✅ Succès: pricePerPerson mis à null\n";
        
        // Vérifier en base
        $session->refresh();
        echo "   Vérification en base: price_per_person = " . ($session->price_per_person ?? 'NULL') . "\n";
    } else {
        echo "❌ Erreur: " . $responseData['error']['message'] . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Mettre les deux à null en même temps
echo "=== Test 3: Mise à jour des deux champs à null ===\n";

// D'abord, remettre des valeurs
$session->update([
    'max_participants' => 6,
    'price_per_person' => 15.75,
]);

echo "   Valeurs avant: max_participants=6, price_per_person=15.75\n";

$request = Request::create("/api/sessions/{$session->id}", 'PUT', [
    'maxParticipants' => null,
    'pricePerPerson' => null,
]);

$request->setUserResolver(function () use ($user) {
    return $user;
});

try {
    $response = $controller($request, $session->id);
    $responseData = json_decode($response->getContent(), true);
    
    if ($response->getStatusCode() === 200) {
        echo "✅ Succès: Les deux champs mis à null\n";
        
        // Vérifier en base
        $session->refresh();
        echo "   Vérification en base:\n";
        echo "   - max_participants = " . ($session->max_participants ?? 'NULL') . "\n";
        echo "   - price_per_person = " . ($session->price_per_person ?? 'NULL') . "\n";
    } else {
        echo "❌ Erreur: " . $responseData['error']['message'] . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Test de validation avec valeur invalide
echo "=== Test 4: Validation avec valeur invalide ===\n";

$request = Request::create("/api/sessions/{$session->id}", 'PUT', [
    'maxParticipants' => 0, // Valeur invalide
]);

$request->setUserResolver(function () use ($user) {
    return $user;
});

try {
    $response = $controller($request, $session->id);
    $responseData = json_decode($response->getContent(), true);
    
    if ($response->getStatusCode() === 400) {
        echo "✅ Succès: Validation échoue correctement pour maxParticipants=0\n";
        echo "   Message d'erreur: " . $responseData['error']['message'] . "\n";
    } else {
        echo "❌ Erreur: La validation aurait dû échouer\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== Tests terminés ===\n";

// Nettoyer
$session->delete();
$user->delete();
echo "✅ Nettoyage effectué\n";
