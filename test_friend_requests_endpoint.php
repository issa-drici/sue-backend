<?php

// Test pour vérifier l'endpoint GET /api/users/friend-requests
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

echo "=== TEST ENDPOINT GET /api/users/friend-requests ===\n\n";

// 1. Login avec l'utilisateur spécifié
echo "🔐 LOGIN UTILISATEUR\n";
echo "===================\n";

$loginResult = testEndpoint('POST', '/login', [
    'email' => 'driciissa76@gmail.com',
    'password' => 'Asmaa1997!',
    'device_name' => 'test-device'
], null, 'Se connecter avec driciissa76@gmail.com');

$loginData = json_decode($loginResult['response'], true);
$token = $loginData['token'] ?? null;
$userId = $loginData['user']['id'] ?? null;

if ($token) {
    echo "   ✅ Token obtenu: " . substr($token, 0, 20) . "...\n";
    echo "   📊 ID utilisateur: $userId\n";
    echo "   📊 Email: " . $loginData['user']['email'] . "\n";
    echo "   📊 Nom: " . $loginData['user']['firstname'] . " " . $loginData['user']['lastname'] . "\n\n";
} else {
    echo "   ❌ Échec de la connexion\n";
    echo "   Réponse: " . $loginResult['response'] . "\n\n";
    exit;
}

// 2. Vérifier les demandes d'ami reçues
echo "📥 DEMANDES D'AMI REÇUES\n";
echo "========================\n";

$friendRequestsResult = testEndpoint('GET', '/users/friend-requests', [], $token, 'Récupérer les demandes d\'ami reçues');

$friendRequestsData = json_decode($friendRequestsResult['response'], true);

if (isset($friendRequestsData['success']) && $friendRequestsData['success']) {
    echo "   ✅ Requête réussie\n";
    echo "   📊 Nombre de demandes reçues: " . count($friendRequestsData['data']) . "\n";
    echo "   📊 Pagination: Page " . $friendRequestsData['pagination']['page'] . " sur " . $friendRequestsData['pagination']['totalPages'] . "\n";
    echo "   📊 Total: " . $friendRequestsData['pagination']['total'] . " demandes\n";

    if (count($friendRequestsData['data']) > 0) {
        echo "   📋 Détails des demandes:\n";
        foreach ($friendRequestsData['data'] as $index => $request) {
            echo "      " . ($index + 1) . ". ID: " . $request['id'] . "\n";
            echo "         Expéditeur: " . $request['sender']['firstname'] . " " . $request['sender']['lastname'] . "\n";
            echo "         Email: " . $request['sender']['email'] . "\n";
            echo "         Statut: " . $request['status'] . "\n";
            echo "         Date: " . $request['created_at'] . "\n";
        }
    } else {
        echo "   ℹ️  Aucune demande d'ami reçue\n";
    }
} else {
    echo "   ❌ Échec de la requête\n";
    echo "   Erreur: " . ($friendRequestsData['error']['message'] ?? 'Erreur inconnue') . "\n";
}

echo "\n";

// 3. Vérifier les demandes d'ami envoyées (en cherchant dans la recherche)
echo "📤 VÉRIFICATION DEMANDES ENVOYÉES\n";
echo "=================================\n";

$searchResult = testEndpoint('GET', '/users/search?q=test', [], $token, 'Rechercher des utilisateurs pour voir les demandes envoyées');

$searchData = json_decode($searchResult['response'], true);

if (isset($searchData['data']) && count($searchData['data']) > 0) {
    echo "   📊 Utilisateurs trouvés: " . count($searchData['data']) . "\n";

    $pendingSentCount = 0;
    $pendingReceivedCount = 0;
    $cancelledCount = 0;

    foreach ($searchData['data'] as $user) {
        $status = $user['relationship']['status'];
        $hasPendingRequest = $user['relationship']['hasPendingRequest'];

        if ($status === 'pending' && $hasPendingRequest) {
            $pendingSentCount++;
            echo "   📤 Demande envoyée vers: " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";
        } elseif ($status === 'pending' && !$hasPendingRequest) {
            $pendingReceivedCount++;
            echo "   📥 Demande reçue de: " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";
        } elseif ($status === 'cancelled') {
            $cancelledCount++;
            echo "   ❌ Demande annulée avec: " . $user['firstname'] . " " . $user['lastname'] . " (" . $user['email'] . ")\n";
        }
    }

    echo "   📊 Résumé:\n";
    echo "      - Demandes envoyées: $pendingSentCount\n";
    echo "      - Demandes reçues: $pendingReceivedCount\n";
    echo "      - Demandes annulées: $cancelledCount\n";
} else {
    echo "   ❌ Aucun utilisateur trouvé\n";
}

echo "\n";

// 4. Vérifier directement dans la base de données
echo "🔍 VÉRIFICATION BASE DE DONNÉES\n";
echo "===============================\n";

// Utiliser artisan tinker pour vérifier les données
$dbCheckCommand = "php artisan tinker --execute=\"echo 'Demandes où l\\'utilisateur est destinataire:' . PHP_EOL; \$received = \\App\\Models\\FriendRequestModel::with('sender')->where('receiver_id', '$userId')->where('status', 'pending')->get(); foreach(\$received as \$req) { echo '  - Reçue de: ' . \$req->sender->firstname . ' ' . \$req->sender->lastname . ' (' . \$req->sender->email . ')' . PHP_EOL; } echo 'Demandes où l\\'utilisateur est expéditeur:' . PHP_EOL; \$sent = \\App\\Models\\FriendRequestModel::with('receiver')->where('sender_id', '$userId')->where('status', 'pending')->get(); foreach(\$sent as \$req) { echo '  - Envoyée vers: ' . \$req->receiver->firstname . ' ' . \$req->receiver->lastname . ' (' . \$req->receiver->email . ')' . PHP_EOL; } echo 'Total demandes reçues: ' . \$received->count() . PHP_EOL; echo 'Total demandes envoyées: ' . \$sent->count() . PHP_EOL;\"";
system($dbCheckCommand);

echo "\n=== FIN DU TEST ===\n";
