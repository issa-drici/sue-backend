<?php

// Test pour vérifier les informations de relation dans la recherche
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

echo "=== TEST RECHERCHE AVEC INFORMATIONS DE RELATION ===\n\n";

// 1. Créer un utilisateur principal
echo "🔐 CRÉATION UTILISATEUR PRINCIPAL\n";
echo "==================================\n";

$createMainUserResult = testEndpoint('POST', '/register', [
    'email' => 'main.user@example.com',
    'password' => 'password123',
    'firstname' => 'Main',
    'lastname' => 'User',
    'device_name' => 'test-device'
], null, 'Créer l\'utilisateur principal');

// 2. Créer un utilisateur ami
echo "🔐 CRÉATION UTILISATEUR AMI\n";
echo "============================\n";

$createFriendResult = testEndpoint('POST', '/register', [
    'email' => 'friend.user@example.com',
    'password' => 'password123',
    'firstname' => 'Friend',
    'lastname' => 'User',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur ami');

// 3. Créer un utilisateur avec demande en attente
echo "🔐 CRÉATION UTILISATEUR DEMANDE EN ATTENTE\n";
echo "==========================================\n";

$createPendingResult = testEndpoint('POST', '/register', [
    'email' => 'pending.user@example.com',
    'password' => 'password123',
    'firstname' => 'Pending',
    'lastname' => 'User',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur avec demande en attente');

// 4. Créer un utilisateur sans relation
echo "🔐 CRÉATION UTILISATEUR SANS RELATION\n";
echo "======================================\n";

$createNoRelationResult = testEndpoint('POST', '/register', [
    'email' => 'norelation.user@example.com',
    'password' => 'password123',
    'firstname' => 'NoRelation',
    'lastname' => 'User',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur sans relation');

// 5. Login avec l'utilisateur principal
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'main.user@example.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter avec l\'utilisateur principal');

$loginData = json_decode($loginResult['response'], true);
$mainToken = $loginData['token'] ?? null;

if ($mainToken) {
    echo "   Token principal obtenu: " . substr($mainToken, 0, 20) . "...\n\n";
}

// 6. Login avec l'utilisateur ami pour accepter la demande
$loginFriendResult = testEndpoint('POST', '/login', [
    'email' => 'friend.user@example.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter avec l\'utilisateur ami');

$loginFriendData = json_decode($loginFriendResult['response'], true);
$friendToken = $loginFriendData['token'] ?? null;

// 7. Envoyer une demande d'ami à l'utilisateur ami
echo "🤝 ENVOI DEMANDE D'AMI\n";
echo "======================\n";

$sendFriendRequestResult = testEndpoint('POST', '/users/friend-requests', [
    'receiver_id' => json_decode($createFriendResult['response'], true)['user']['id']
], $mainToken, 'Envoyer une demande d\'ami');

// 8. Accepter la demande d'ami (simuler l'acceptation)
echo "🤝 ACCEPTATION DEMANDE D'AMI\n";
echo "============================\n";

// Récupérer l'ID de la demande
$getRequestsResult = testEndpoint('GET', '/users/friend-requests', null, $friendToken, 'Récupérer les demandes d\'ami reçues');

$requestsData = json_decode($getRequestsResult['response'], true);
if (isset($requestsData['data']) && count($requestsData['data']) > 0) {
    $requestId = $requestsData['data'][0]['id'];

    $acceptRequestResult = testEndpoint('PATCH', "/users/friend-requests/$requestId", [
        'status' => 'accepted'
    ], $friendToken, 'Accepter la demande d\'ami');
} else {
    echo "   ❌ Aucune demande d'ami trouvée\n";
}

// 9. Envoyer une demande d'ami à l'utilisateur en attente
echo "🤝 ENVOI DEMANDE D'AMI EN ATTENTE\n";
echo "==================================\n";

$sendPendingRequestResult = testEndpoint('POST', '/users/friend-requests', [
    'receiver_id' => json_decode($createPendingResult['response'], true)['user']['id']
], $mainToken, 'Envoyer une demande d\'ami en attente');

// 10. Test recherche avec informations de relation
echo "🔍 TEST RECHERCHE AVEC RELATIONS\n";
echo "=================================\n";

$searchResult = testEndpoint('GET', '/users/search?q=user', null, $mainToken, 'Rechercher tous les utilisateurs avec "user"');

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

// 11. Test recherche spécifique par email
echo "🔍 TEST RECHERCHE SPÉCIFIQUE\n";
echo "=============================\n";

$searchSpecificResult = testEndpoint('GET', '/users/search?q=friend.user@example.com', null, $mainToken, 'Rechercher l\'utilisateur ami spécifiquement');

$searchSpecificData = json_decode($searchSpecificResult['response'], true);
if (isset($searchSpecificData['data']) && count($searchSpecificData['data']) > 0) {
    $friendUser = $searchSpecificData['data'][0];
    echo "   👤 " . $friendUser['firstname'] . " " . $friendUser['lastname'] . " (" . $friendUser['email'] . ")\n";

    if (isset($friendUser['relationship'])) {
        $rel = $friendUser['relationship'];
        echo "      📊 Statut: " . $rel['status'] . " (devrait être 'friend')\n";
        echo "      🤝 Ami: " . ($rel['isFriend'] ? 'Oui' : 'Non') . " (devrait être Oui)\n";
        echo "      ⏳ Demande en attente: " . ($rel['hasPendingRequest'] ? 'Oui' : 'Non') . " (devrait être Non)\n";
        echo "      👥 Amis en commun: " . $rel['mutualFriends'] . "\n";
    }
} else {
    echo "   Aucun résultat trouvé\n";
}

echo "\n=== FIN DU TEST ===\n";
