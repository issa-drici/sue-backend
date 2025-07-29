<?php

// Test simple pour vérifier l'endpoint /api/profile
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

echo "=== TEST SIMPLE /API/PROFILE ===\n\n";

// Login avec l'utilisateur existant
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'user@profile.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter');

$userData = json_decode($loginResult['response'], true);
if (isset($userData['token'])) {
    $token = $userData['token'];

    echo "   Token obtenu: " . substr($token, 0, 20) . "...\n\n";

    // Tester l'endpoint GET /api/profile
    echo "📋 TEST GET /PROFILE\n";
    echo "===================\n";

    $profileResult = testEndpoint('GET', '/profile', null, $token, 'Récupérer le profil');

    if ($profileResult['code'] === 200) {
        $profileData = json_decode($profileResult['response'], true);
        echo "   Réponse complète:\n";
        echo "   " . json_encode($profileData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

        // Analyser la structure
        if (isset($profileData['user'])) {
            echo "📊 STRUCTURE AVEC 'user':\n";
            $user = $profileData['user'];
            echo "   - ID: " . ($user['id'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Firstname: " . ($user['firstname'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Lastname: " . ($user['lastname'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Email: " . ($user['email'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Avatar URL: " . ($user['avatar_url'] ?? 'NON TROUVÉ') . "\n";
        } elseif (isset($profileData['data'])) {
            echo "📊 STRUCTURE AVEC 'data':\n";
            $data = $profileData['data'];
            echo "   - ID: " . ($data['id'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Firstname: " . ($data['firstname'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Lastname: " . ($data['lastname'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Email: " . ($data['email'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Avatar: " . ($data['avatar'] ?? 'NON TROUVÉ') . "\n";
        } else {
            echo "📊 STRUCTURE INCONNUE:\n";
            echo "   Clés disponibles: " . implode(', ', array_keys($profileData)) . "\n";
        }
    }

} else {
    echo "   ❌ Erreur lors de la connexion\n";
}

echo "\n=== TEST TERMINÉ ===\n";
