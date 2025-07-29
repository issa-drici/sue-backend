<?php

// Test pour vérifier que GET /sessions retourne uniquement les sessions de l'utilisateur connecté
$baseUrl = 'http://localhost:8000/api';

function testEndpoint($method, $endpoint, $data = null, $token = null, $description = '') {
    global $baseUrl;

    $url = $baseUrl . $endpoint;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
    }

    if ($token) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $status = ($httpCode >= 200 && $httpCode < 300) ? '✅' : '❌';
    echo "$status $method $endpoint - Code: $httpCode ($description)\n";

    return ['code' => $httpCode, 'response' => $response];
}

echo "=== TEST FILTRAGE SESSIONS PAR UTILISATEUR ===\n\n";

// 1. Créer un premier utilisateur
echo "🔐 CRÉATION PREMIER UTILISATEUR\n";
echo "===============================\n";

$createUser1Result = testEndpoint('POST', '/register', [
    'email' => 'user1@example.com',
    'password' => 'password123',
    'firstname' => 'User',
    'lastname' => 'One',
    'device_name' => 'test-device'
], null, 'Créer le premier utilisateur');

// 2. Login avec le premier utilisateur
$login1Result = testEndpoint('POST', '/login', [
    'email' => 'user1@example.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter avec le premier utilisateur');

$login1Data = json_decode($login1Result['response'], true);
$token1 = $login1Data['token'] ?? null;
$userId1 = $login1Data['user']['id'] ?? null;

if ($token1) {
    echo "   Token User1: " . substr($token1, 0, 20) . "...\n";
    echo "   User1 ID: $userId1\n\n";
}

// 3. Créer un deuxième utilisateur
echo "🔐 CRÉATION DEUXIÈME UTILISATEUR\n";
echo "================================\n";

$createUser2Result = testEndpoint('POST', '/register', [
    'email' => 'user2@example.com',
    'password' => 'password123',
    'firstname' => 'User',
    'lastname' => 'Two',
    'device_name' => 'test-device'
], null, 'Créer le deuxième utilisateur');

// 4. Login avec le deuxième utilisateur
$login2Result = testEndpoint('POST', '/login', [
    'email' => 'user2@example.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter avec le deuxième utilisateur');

$login2Data = json_decode($login2Result['response'], true);
$token2 = $login2Data['token'] ?? null;
$userId2 = $login2Data['user']['id'] ?? null;

if ($token2) {
    echo "   Token User2: " . substr($token2, 0, 20) . "...\n";
    echo "   User2 ID: $userId2\n\n";
}

// 5. Créer une session avec User1
echo "🏃 CRÉATION SESSION AVEC USER1\n";
echo "==============================\n";

$createSessionResult = testEndpoint('POST', '/sessions', [
    'sport' => 'tennis',
    'date' => date('Y-m-d', strtotime('+1 week')),
    'time' => '18:00',
    'location' => 'Tennis Club de Paris'
], $token1, 'Créer une session avec User1');

$sessionData = json_decode($createSessionResult['response'], true);
$sessionId = $sessionData['data']['id'] ?? null;

if ($sessionId) {
    echo "   Session créée avec ID: $sessionId\n\n";
}

// 6. Tester GET /sessions avec User1 (doit voir sa session)
echo "📋 TEST GET /SESSIONS AVEC USER1\n";
echo "=================================\n";

$getSessionsUser1Result = testEndpoint('GET', '/sessions', null, $token1, 'Obtenir les sessions de User1');

$sessionsUser1 = json_decode($getSessionsUser1Result['response'], true);
if (isset($sessionsUser1['data'])) {
    echo "   Nombre de sessions pour User1: " . count($sessionsUser1['data']) . "\n";
    foreach ($sessionsUser1['data'] as $session) {
        echo "   - Session: " . $session['sport'] . " le " . $session['date'] . " (ID: " . $session['id'] . ")\n";
    }
    echo "\n";
}

// 7. Tester GET /sessions avec User2 (ne doit pas voir la session de User1)
echo "📋 TEST GET /SESSIONS AVEC USER2\n";
echo "=================================\n";

$getSessionsUser2Result = testEndpoint('GET', '/sessions', null, $token2, 'Obtenir les sessions de User2');

$sessionsUser2 = json_decode($getSessionsUser2Result['response'], true);
if (isset($sessionsUser2['data'])) {
    echo "   Nombre de sessions pour User2: " . count($sessionsUser2['data']) . "\n";
    foreach ($sessionsUser2['data'] as $session) {
        echo "   - Session: " . $session['sport'] . " le " . $session['date'] . " (ID: " . $session['id'] . ")\n";
    }
    echo "\n";
}

// 8. Inviter User2 à la session de User1
echo "📨 INVITATION USER2 À LA SESSION\n";
echo "=================================\n";

if ($sessionId && $userId2) {
    // Note: Il faudrait un endpoint pour inviter, mais pour ce test on utilise directement la base de données
    // ou on peut tester avec l'endpoint de réponse si il existe
    echo "   Note: L'invitation nécessiterait un endpoint dédié\n\n";
}

// 9. Créer une session avec User2
echo "🏃 CRÉATION SESSION AVEC USER2\n";
echo "==============================\n";

$createSession2Result = testEndpoint('POST', '/sessions', [
    'sport' => 'golf',
    'date' => date('Y-m-d', strtotime('+2 weeks')),
    'time' => '14:00',
    'location' => 'Golf Club de Paris'
], $token2, 'Créer une session avec User2');

$session2Data = json_decode($createSession2Result['response'], true);
$session2Id = $session2Data['data']['id'] ?? null;

if ($session2Id) {
    echo "   Session2 créée avec ID: $session2Id\n\n";
}

// 10. Tester GET /sessions avec User1 (ne doit voir que sa session)
echo "📋 TEST FINAL GET /SESSIONS AVEC USER1\n";
echo "=======================================\n";

$getSessionsUser1FinalResult = testEndpoint('GET', '/sessions', null, $token1, 'Obtenir les sessions de User1 (final)');

$sessionsUser1Final = json_decode($getSessionsUser1FinalResult['response'], true);
if (isset($sessionsUser1Final['data'])) {
    echo "   Nombre de sessions pour User1: " . count($sessionsUser1Final['data']) . "\n";
    foreach ($sessionsUser1Final['data'] as $session) {
        echo "   - Session: " . $session['sport'] . " le " . $session['date'] . " (ID: " . $session['id'] . ")\n";
    }
    echo "\n";
}

// 11. Tester GET /sessions avec User2 (doit voir sa session)
echo "📋 TEST FINAL GET /SESSIONS AVEC USER2\n";
echo "=======================================\n";

$getSessionsUser2FinalResult = testEndpoint('GET', '/sessions', null, $token2, 'Obtenir les sessions de User2 (final)');

$sessionsUser2Final = json_decode($getSessionsUser2FinalResult['response'], true);
if (isset($sessionsUser2Final['data'])) {
    echo "   Nombre de sessions pour User2: " . count($sessionsUser2Final['data']) . "\n";
    foreach ($sessionsUser2Final['data'] as $session) {
        echo "   - Session: " . $session['sport'] . " le " . $session['date'] . " (ID: " . $session['id'] . ")\n";
    }
    echo "\n";
}

echo "=== FIN DU TEST ===\n";
