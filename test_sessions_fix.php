<?php

// Test des corrections des endpoints de sessions
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

    if ($httpCode >= 400) {
        echo "   Erreur: " . substr($response, 0, 200) . (strlen($response) > 200 ? "..." : "") . "\n";
    } else {
        echo "   Réponse: " . substr($response, 0, 300) . (strlen($response) > 300 ? "..." : "") . "\n";
    }

    return ['code' => $httpCode, 'response' => $response];
}

echo "=== TEST DES CORRECTIONS DES ENDPOINTS SESSIONS ===\n\n";

// 1. Créer un utilisateur de test
echo "🔐 CRÉATION UTILISATEUR DE TEST\n";
echo "===============================\n";

$createUserResult = testEndpoint('POST', '/register', [
    'email' => 'sessiontest@example.com',
    'password' => 'password123',
    'firstname' => 'Session',
    'lastname' => 'Test',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur de test');

// 2. Login avec l'utilisateur
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'sessiontest@example.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter avec l\'utilisateur de test');

// Extraire le token
$loginData = json_decode($loginResult['response'], true);
if (isset($loginData['token'])) {
    $token = $loginData['token'];
    echo "   Token obtenu: " . substr($token, 0, 20) . "...\n\n";
}

// 3. Créer une session
echo "🏃 CRÉATION DE SESSION\n";
echo "=====================\n";

$createSessionResult = testEndpoint('POST', '/sessions', [
    'sport' => 'tennis',
    'date' => date('Y-m-d', strtotime('+1 week')),
    'time' => '18:00',
    'location' => 'Tennis Club de Paris'
], $token, 'Créer une session de test');

// Extraire l'ID de session
$sessionData = json_decode($createSessionResult['response'], true);
if (isset($sessionData['data']['id'])) {
    $sessionId = $sessionData['data']['id'];
    echo "   Session créée avec ID: $sessionId\n\n";
}

// 4. Tester l'endpoint GET /sessions (corrigé)
echo "📋 TEST ENDPOINT GET /SESSIONS (CORRIGÉ)\n";
echo "========================================\n";

$getSessionsResult = testEndpoint('GET', '/sessions', null, $token, 'Obtenir toutes les sessions');

// 5. Tester l'endpoint GET /sessions/my-created (nouveau)
echo "\n📋 TEST ENDPOINT GET /SESSIONS/MY-CREATED (NOUVEAU)\n";
echo "==================================================\n";

$getMyCreatedResult = testEndpoint('GET', '/sessions/my-created', null, $token, 'Obtenir mes sessions créées');

// 6. Tester l'endpoint GET /sessions/my-participations (nouveau)
echo "\n📋 TEST ENDPOINT GET /SESSIONS/MY-PARTICIPATIONS (NOUVEAU)\n";
echo "==========================================================\n";

$getMyParticipationsResult = testEndpoint('GET', '/sessions/my-participations', null, $token, 'Obtenir mes participations');

// 7. Tester l'endpoint GET /sessions/{id} (déjà fonctionnel)
echo "\n📋 TEST ENDPOINT GET /SESSIONS/{ID}\n";
echo "===================================\n";

if (isset($sessionId)) {
    $getSessionByIdResult = testEndpoint('GET', "/sessions/$sessionId", null, $token, 'Obtenir une session par ID');
}

echo "\n=== FIN DU TEST ===\n";
