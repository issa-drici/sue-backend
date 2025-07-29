<?php

// Test simple pour vérifier les informations de relation
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

echo "=== TEST SIMPLE RECHERCHE AVEC RELATIONS ===\n\n";

// 1. Login avec un utilisateur existant
echo "🔐 LOGIN UTILISATEUR\n";
echo "====================\n";

$loginResult = testEndpoint('POST', '/login', [
    'email' => 'main.user@example.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter avec main.user@example.com');

$loginData = json_decode($loginResult['response'], true);
$token = $loginData['token'] ?? null;

if ($token) {
    echo "   Token obtenu: " . substr($token, 0, 20) . "...\n\n";
}

// 2. Test recherche avec informations de relation
echo "🔍 TEST RECHERCHE AVEC RELATIONS\n";
echo "=================================\n";

$searchResult = testEndpoint('GET', '/users/search?q=test', null, $token, 'Rechercher utilisateurs avec "test"');

$searchData = json_decode($searchResult['response'], true);
if (isset($searchData['data'])) {
    echo "   Nombre de résultats: " . count($searchData['data']) . "\n\n";

    foreach ($searchData['data'] as $user) {
        echo "   👤 " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";

        if (isset($user['relationship'])) {
            $rel = $user['relationship'];
            echo "      📊 Statut: " . $rel['status'] . "\n";
            echo "      🤝 Ami: " . ($rel['isFriend'] ? 'Oui' : 'Non') . "\n";
            echo "      ⏳ Demande en attente: " . ($rel['hasPendingRequest'] ? 'Oui' : 'Non') . "\n";
            echo "      👥 Amis en commun: " . $rel['mutualFriends'] . "\n";
        } else {
            echo "      ❌ Pas d'informations de relation\n";
        }
        echo "\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}

// 3. Test recherche de l'utilisateur connecté lui-même
echo "🔍 TEST RECHERCHE UTILISATEUR CONNECTÉ\n";
echo "=======================================\n";

$searchSelfResult = testEndpoint('GET', '/users/search?q=main.user@example.com', null, $token, 'Rechercher l\'utilisateur connecté');

$searchSelfData = json_decode($searchSelfResult['response'], true);
if (isset($searchSelfData['data']) && count($searchSelfData['data']) > 0) {
    $selfUser = $searchSelfData['data'][0];
    echo "   👤 " . $selfUser['firstname'] . " " . $selfUser['lastname'] . " (" . $selfUser['email'] . ")\n";

    if (isset($selfUser['relationship'])) {
        $rel = $selfUser['relationship'];
        echo "      📊 Statut: " . $rel['status'] . " (devrait être 'self')\n";
        echo "      🤝 Ami: " . ($rel['isFriend'] ? 'Oui' : 'Non') . " (devrait être Non)\n";
        echo "      ⏳ Demande en attente: " . ($rel['hasPendingRequest'] ? 'Oui' : 'Non') . " (devrait être Non)\n";
        echo "      👥 Amis en commun: " . $rel['mutualFriends'] . " (devrait être 0)\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}

// 4. Test recherche par prénom
echo "🔍 TEST RECHERCHE PAR PRÉNOM\n";
echo "=============================\n";

$searchfirstnameResult = testEndpoint('GET', '/users/search?q=Main', null, $token, 'Rechercher par prénom "Main"');

$searchfirstnameData = json_decode($searchfirstnameResult['response'], true);
if (isset($searchfirstnameData['data']) && count($searchfirstnameData['data']) > 0) {
    $firstnameUser = $searchfirstnameData['data'][0];
    echo "   👤 " . $firstnameUser['firstname'] . " " . $firstnameUser['lastname'] . " (" . $firstnameUser['email'] . ")\n";

    if (isset($firstnameUser['relationship'])) {
        $rel = $firstnameUser['relationship'];
        echo "      📊 Statut: " . $rel['status'] . " (devrait être 'self')\n";
        echo "      🤝 Ami: " . ($rel['isFriend'] ? 'Oui' : 'Non') . " (devrait être Non)\n";
        echo "      ⏳ Demande en attente: " . ($rel['hasPendingRequest'] ? 'Oui' : 'Non') . " (devrait être Non)\n";
        echo "      👥 Amis en commun: " . $rel['mutualFriends'] . " (devrait être 0)\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}

echo "\n=== FIN DU TEST ===\n";
