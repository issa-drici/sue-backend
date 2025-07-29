<?php

// Test pour vérifier l'endpoint GET /api/users/friends
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

echo "=== TEST ENDPOINT GET /api/users/friends ===\n\n";

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
    echo "   📊 Email: " . $loginData['user']['email'] . "\n\n";
} else {
    echo "   ❌ Échec de la connexion\n";
    echo "   Réponse: " . $loginResult['response'] . "\n\n";
    exit;
}

// 2. Vérifier les amis
echo "👥 LISTE DES AMIS\n";
echo "=================\n";

$friendsResult = testEndpoint('GET', '/users/friends', [], $token, 'Récupérer la liste des amis');

$friendsData = json_decode($friendsResult['response'], true);

if (isset($friendsData['success']) && $friendsData['success']) {
    echo "   ✅ Requête réussie\n";
    echo "   📊 Nombre d'amis: " . count($friendsData['data']) . "\n";
    echo "   📊 Pagination: Page " . $friendsData['pagination']['page'] . " sur " . $friendsData['pagination']['totalPages'] . "\n";
    echo "   📊 Total: " . $friendsData['pagination']['total'] . " amis\n";

    if (count($friendsData['data']) > 0) {
        echo "   📋 Détails des amis:\n";
        foreach ($friendsData['data'] as $index => $friend) {
            echo "      " . ($index + 1) . ". ID: " . ($friend['id'] ?? 'N/A') . "\n";
            echo "         Nom: " . ($friend['firstname'] ?? 'N/A') . " " . ($friend['lastname'] ?? 'N/A') . "\n";
            echo "         Email: " . ($friend['email'] ?? 'N/A') . "\n";
            echo "         Avatar: " . ($friend['avatar'] ?? 'null') . "\n";
            echo "         Status: " . ($friend['status'] ?? 'N/A') . "\n";
            echo "         Last Seen: " . ($friend['lastSeen'] ?? 'N/A') . "\n";
        }
    } else {
        echo "   ℹ️  Aucun ami trouvé\n";
    }
} else {
    echo "   ❌ Échec de la requête\n";
    echo "   Erreur: " . ($friendsData['error']['message'] ?? 'Erreur inconnue') . "\n";
}

echo "\n";

// 3. Vérifier directement dans la base de données
echo "🔍 VÉRIFICATION BASE DE DONNÉES\n";
echo "===============================\n";

// Utiliser artisan tinker pour vérifier les données
$dbCheckCommand = "php artisan tinker --execute=\"echo 'Amis de l\\'utilisateur:' . PHP_EOL; \$friends = \\App\\Models\\FriendModel::with('friend')->where('user_id', '$userId')->get(); foreach(\$friends as \$friend) { echo '  - ' . \$friend->friend->firstname . ' ' . \$friend->friend->lastname . ' (' . \$friend->friend->email . ')' . PHP_EOL; } echo 'Total amis: ' . \$friends->count() . PHP_EOL;\"";
system($dbCheckCommand);

echo "\n=== FIN DU TEST ===\n";
