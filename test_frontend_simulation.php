<?php

// Test pour simuler exactement ce que fait le frontend
$baseUrl = 'http://localhost:8000/api';

function testEndpoint($method, $endpoint, $data = null, $token = null, $description = '') {
    global $baseUrl;

    $url = $baseUrl . $endpoint;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_VERBOSE, true);

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'
    ];

    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $status = ($httpCode >= 200 && $httpCode < 300) ? '✅' : '❌';
    echo "$status $method $endpoint - Code: $httpCode ($description)\n";

    if ($error) {
        echo "   Curl Error: $error\n";
    }

    return ['code' => $httpCode, 'response' => $response, 'error' => $error];
}

echo "=== SIMULATION FRONTEND ===\n\n";

// 1. Login pour obtenir un token
echo "🔐 LOGIN POUR OBTENIR TOKEN\n";
echo "============================\n";

$loginResult = testEndpoint('POST', '/login', [
    'email' => 'user@auth.com',
    'password' => 'password123',
    'device_name' => 'mobile-app'
], null, 'Login pour obtenir token');

$userData = json_decode($loginResult['response'], true);
if (isset($userData['token'])) {
    $token = $userData['token'];

    echo "   Token obtenu: " . substr($token, 0, 30) . "...\n\n";

    // 2. Simuler plusieurs appels à /sessions avec le même token
    echo "📱 SIMULATION APPELS FRONTEND\n";
    echo "==============================\n";

    for ($i = 1; $i <= 3; $i++) {
        echo "\n--- Appel $i ---\n";
        $sessionsResult = testEndpoint('GET', '/sessions', null, $token, "Appel $i - Récupérer sessions");

        if ($sessionsResult['code'] === 200) {
            $sessionsData = json_decode($sessionsResult['response'], true);
            echo "   ✅ Succès - Sessions: " . count($sessionsData['data'] ?? []) . "\n";
        } else {
            echo "   ❌ Échec - Code: " . $sessionsResult['code'] . "\n";
            echo "   Réponse: " . $sessionsResult['response'] . "\n";
        }
    }

    // 3. Tester avec différents formats de header
    echo "\n🔧 TEST DIFFÉRENTS FORMATS HEADER\n";
    echo "==================================\n";

    // Test avec header Authorization standard
    echo "\n--- Header Authorization standard ---\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/sessions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo ($httpCode === 200 ? "✅" : "❌") . " Code: $httpCode\n";

    // Test avec header Authorization sans Bearer
    echo "\n--- Header Authorization sans Bearer ---\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/sessions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . $token,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo ($httpCode === 200 ? "✅" : "❌") . " Code: $httpCode\n";

    // Test avec header X-API-Key (pour voir si c'est un problème de format)
    echo "\n--- Header X-API-Key ---\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/sessions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Key: ' . $token,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo ($httpCode === 200 ? "✅" : "❌") . " Code: $httpCode\n";

    // 4. Tester avec un token expiré (simulation)
    echo "\n⏰ TEST TOKEN EXPIRÉ\n";
    echo "====================\n";

    // Créer un token "expiré" en modifiant le dernier caractère
    $expiredToken = substr($token, 0, -1) . 'X';
    $expiredResult = testEndpoint('GET', '/sessions', null, $expiredToken, 'Tester avec token expiré');

    echo "   Code: " . $expiredResult['code'] . "\n";
    echo "   Réponse: " . $expiredResult['response'] . "\n";

} else {
    echo "   ❌ Erreur lors de la connexion\n";
    echo "   Réponse: " . $loginResult['response'] . "\n";
}

echo "\n=== SIMULATION TERMINÉE ===\n";
