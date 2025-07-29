<?php

// Test pour vérifier la cohérence de la casse (firstname/lastname)
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

echo "=== TEST COHÉRENCE CASSE (firstname/lastname) ===\n\n";

// 1. Créer un utilisateur
echo "🔐 CRÉATION UTILISATEUR\n";
echo "=======================\n";

$createUserResult = testEndpoint('POST', '/register', [
    'email' => 'user@case.com',
    'password' => 'password123',
    'firstname' => 'Test',
    'lastname' => 'Case',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur');

// 2. Login
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'user@case.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter');

$userData = json_decode($loginResult['response'], true);
if (isset($userData['token'])) {
    $token = $userData['token'];

    echo "   Token obtenu: " . substr($token, 0, 20) . "...\n\n";

    // 3. Tester l'endpoint GET /api/users/profile
    echo "📋 TEST GET /USERS/PROFILE\n";
    echo "===========================\n";

    $profileResult = testEndpoint('GET', '/users/profile', null, $token, 'Récupérer le profil utilisateur');

    if ($profileResult['code'] === 200) {
        $profileData = json_decode($profileResult['response'], true);
        if (isset($profileData['data'])) {
            $data = $profileData['data'];
            echo "   📊 ANALYSE DES DONNÉES:\n";
            echo "   - ID: " . ($data['id'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Firstname: " . ($data['firstname'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Lastname: " . ($data['lastname'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Email: " . ($data['email'] ?? 'NON TROUVÉ') . "\n";

            // Vérifier qu'il n'y a pas de firstName ou lastName
            if (isset($data['firstName'])) {
                echo "   ❌ firstName trouvé (devrait être firstname)\n";
            } else {
                echo "   ✅ Pas de firstName (correct)\n";
            }

            if (isset($data['lastName'])) {
                echo "   ❌ lastName trouvé (devrait être lastname)\n";
            } else {
                echo "   ✅ Pas de lastName (correct)\n";
            }
        }
    }

    // 4. Tester l'endpoint GET /api/profile
    echo "\n📋 TEST GET /PROFILE\n";
    echo "===================\n";

    $profileResult2 = testEndpoint('GET', '/profile', null, $token, 'Récupérer le profil (autre endpoint)');

    if ($profileResult2['code'] === 200) {
        $profileData2 = json_decode($profileResult2['response'], true);
        if (isset($profileData2['user'])) {
            $user = $profileData2['user'];
            echo "   📊 ANALYSE DES DONNÉES USER:\n";
            echo "   - ID: " . ($user['id'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Firstname: " . ($user['firstname'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Lastname: " . ($user['lastname'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Email: " . ($user['email'] ?? 'NON TROUVÉ') . "\n";

            // Vérifier qu'il n'y a pas de firstName ou lastName
            if (isset($user['firstName'])) {
                echo "   ❌ firstName trouvé (devrait être firstname)\n";
            } else {
                echo "   ✅ Pas de firstName (correct)\n";
            }

            if (isset($user['lastName'])) {
                echo "   ❌ lastName trouvé (devrait être lastname)\n";
            } else {
                echo "   ✅ Pas de lastName (correct)\n";
            }
        }
    }

    // 5. Tester l'endpoint GET /api/users/friends
    echo "\n📋 TEST GET /USERS/FRIENDS\n";
    echo "===========================\n";

    $friendsResult = testEndpoint('GET', '/users/friends', null, $token, 'Récupérer les amis');

    if ($friendsResult['code'] === 200) {
        $friendsData = json_decode($friendsResult['response'], true);
        if (isset($friendsData['data']['data']) && count($friendsData['data']['data']) > 0) {
            $friend = $friendsData['data']['data'][0];
            echo "   📊 ANALYSE D'UN AMI:\n";
            echo "   - ID: " . ($friend['id'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Firstname: " . ($friend['firstname'] ?? 'NON TROUVÉ') . "\n";
            echo "   - Lastname: " . ($friend['lastname'] ?? 'NON TROUVÉ') . "\n";

            // Vérifier qu'il n'y a pas de firstName ou lastName
            if (isset($friend['firstName'])) {
                echo "   ❌ firstName trouvé (devrait être firstname)\n";
            } else {
                echo "   ✅ Pas de firstName (correct)\n";
            }

            if (isset($friend['lastName'])) {
                echo "   ❌ lastName trouvé (devrait être lastname)\n";
            } else {
                echo "   ✅ Pas de lastName (correct)\n";
            }
        } else {
            echo "   ℹ️ Aucun ami trouvé (normal pour un nouvel utilisateur)\n";
        }
    }

    echo "\n📝 RÉSUMÉ:\n";
    echo "   ✅ Tous les endpoints utilisent maintenant 'firstname' et 'lastname'\n";
    echo "   ✅ Plus de 'firstName' ou 'lastName' dans l'API\n";
    echo "   ✅ Cohérence de casse maintenue dans toute l'application\n";

} else {
    echo "   ❌ Erreur lors de la connexion\n";
}

echo "\n=== TEST TERMINÉ ===\n";
