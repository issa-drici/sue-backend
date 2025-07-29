<?php

// Test pour vérifier l'endpoint /api/users/profile
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

echo "=== TEST ENDPOINT PROFILE ===\n\n";

// 1. Créer un utilisateur
echo "🔐 CRÉATION UTILISATEUR\n";
echo "=======================\n";

$createUserResult = testEndpoint('POST', '/register', [
    'email' => 'user@profile.com',
    'password' => 'password123',
    'firstname' => 'Jean',
    'lastname' => 'Dupont',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur');

// 2. Login
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'user@profile.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter');

$userData = json_decode($loginResult['response'], true);
if (isset($userData['token'])) {
    $token = $userData['token'];
    $userId = $userData['user']['id'];

    echo "   Token obtenu: " . substr($token, 0, 20) . "...\n";
    echo "   User ID: $userId\n\n";

    // 3. Tester l'endpoint GET /api/users/profile
    echo "📋 TEST GET /USERS/PROFILE\n";
    echo "===========================\n";

    $profileResult = testEndpoint('GET', '/users/profile', null, $token, 'Récupérer le profil utilisateur');

    if ($profileResult['code'] === 200) {
        $profileData = json_decode($profileResult['response'], true);
        echo "   Réponse complète:\n";
        echo "   " . json_encode($profileData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

        if (isset($profileData['data'])) {
            $data = $profileData['data'];
            echo "📊 ANALYSE DES DONNÉES:\n";
            echo "   - ID: " . ($data['id'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Firstname: " . ($data['firstname'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Lastname: " . ($data['lastname'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Email: " . ($data['email'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Avatar: " . ($data['avatar'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Stats: " . (isset($data['stats']) ? 'PRÉSENT' : 'NON TROUVÉ') . "\n";

            // Vérifier si fullName existe
            if (isset($data['fullName'])) {
                echo "   - FullName: " . $data['fullName'] . " (⚠️ NE DEVRAIT PAS EXISTER)\n";
            } else {
                echo "   - FullName: NON TROUVÉ (✅ CORRECT)\n";
            }

            // Vérifier si les données correspondent à l'utilisateur créé
            $expectedFirstname = 'Jean';
            $expectedLastname = 'Dupont';
            $expectedEmail = 'user@profile.com';

            echo "\n🔍 VÉRIFICATION DES DONNÉES:\n";
            echo "   " . (($data['firstname'] ?? '') === $expectedFirstname ? "✅" : "❌") . " Firstname: attendu '$expectedFirstname', reçu '" . ($data['firstname'] ?? 'NON TROUVÉ') . "'\n";
            echo "   " . (($data['lastname'] ?? '') === $expectedLastname ? "✅" : "❌") . " Lastname: attendu '$expectedLastname', reçu '" . ($data['lastname'] ?? 'NON TROUVÉ') . "'\n";
            echo "   " . (($data['email'] ?? '') === $expectedEmail ? "✅" : "❌") . " Email: attendu '$expectedEmail', reçu '" . ($data['email'] ?? 'NON TROUVÉ') . "'\n";
        }
    }

    // 4. Tester aussi l'endpoint GET /api/profile (l'autre endpoint)
    echo "\n📋 TEST GET /PROFILE (AUTRE ENDPOINT)\n";
    echo "=====================================\n";

    $profileResult2 = testEndpoint('GET', '/profile', null, $token, 'Récupérer le profil (autre endpoint)');

    if ($profileResult2['code'] === 200) {
        $profileData2 = json_decode($profileResult2['response'], true);
        echo "   Réponse complète:\n";
        echo "   " . json_encode($profileData2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

        if (isset($profileData2['user'])) {
            $user = $profileData2['user'];
            echo "📊 ANALYSE DES DONNÉES USER:\n";
            echo "   - ID: " . ($user['id'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Firstname: " . ($user['firstname'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Lastname: " . ($user['lastname'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Email: " . ($user['email'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Avatar URL: " . ($user['avatar_url'] ?? 'NON TROUVÉ') . "\n";

            // Vérifier si full_name existe
            if (isset($user['full_name'])) {
                echo "   - Full_name: " . $user['full_name'] . " (⚠️ NE DEVRAIT PAS EXISTER)\n";
            } else {
                echo "   - Full_name: NON TROUVÉ (✅ CORRECT)\n";
            }
        }
    }

} else {
    echo "   ❌ Erreur lors de la connexion\n";
}

echo "\n=== TEST TERMINÉ ===\n";
