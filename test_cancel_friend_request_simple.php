<?php

// Test simple pour l'endpoint d'annulation de demande d'ami
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

echo "=== TEST SIMPLE ENDPOINT ANNULATION DEMANDE D'AMI ===\n\n";

// 1. Login avec un utilisateur existant
echo "🔐 LOGIN UTILISATEUR EXISTANT\n";
echo "=============================\n";

$loginResult = testEndpoint('POST', '/login', [
    'email' => 'cancel.test@example.com',
    'password' => 'password',
    'device_name' => 'test-device'
], null, 'Se connecter avec un utilisateur existant');

$loginData = json_decode($loginResult['response'], true);
$token = $loginData['token'] ?? null;
$userId = $loginData['user']['id'] ?? null;

if ($token) {
    echo "   ✅ Token obtenu: " . substr($token, 0, 20) . "...\n";
    echo "   📊 ID utilisateur: $userId\n\n";
} else {
    echo "   ❌ Échec de la connexion\n";
    echo "   Réponse: " . $loginResult['response'] . "\n\n";
    exit;
}

// 2. Rechercher des utilisateurs pour trouver des cibles
echo "🔍 RECHERCHE D'UTILISATEURS\n";
echo "===========================\n";

$searchResult = testEndpoint('GET', '/users/search?q=test', [], $token, 'Rechercher des utilisateurs');

$searchData = json_decode($searchResult['response'], true);
$targetUserId = null;

if (isset($searchData['data']) && count($searchData['data']) > 0) {
    // Prendre le premier utilisateur qui n'est pas l'utilisateur connecté
    foreach ($searchData['data'] as $user) {
        if ($user['id'] !== $userId) {
            $targetUserId = $user['id'];
            echo "   ✅ Utilisateur cible trouvé: " . $user['firstname'] . " " . $user['lastname'] . " (ID: $targetUserId)\n";
            break;
        }
    }
}

if (!$targetUserId) {
    echo "   ❌ Aucun utilisateur cible trouvé\n";
    echo "   Réponse: " . $searchResult['response'] . "\n\n";
    exit;
}

echo "\n";

// 3. Envoyer une demande d'ami
echo "🤝 ENVOI DEMANDE D'AMI\n";
echo "======================\n";

$sendRequestResult = testEndpoint('POST', '/users/friend-requests', [
    'userId' => $targetUserId
], $token, 'Envoyer une demande d\'ami');

$sendRequestData = json_decode($sendRequestResult['response'], true);
if (isset($sendRequestData['success']) && $sendRequestData['success']) {
    echo "   ✅ Demande d'ami envoyée avec succès\n";
    echo "   📊 De: $userId vers: $targetUserId\n";
} else {
    echo "   ❌ Échec de l'envoi de la demande d'ami\n";
    echo "   Erreur: " . ($sendRequestData['error']['message'] ?? 'Erreur inconnue') . "\n";

    // Si la demande existe déjà, on continue quand même
    if (str_contains($sendRequestData['error']['message'] ?? '', 'existe déjà')) {
        echo "   ℹ️  La demande existe déjà, on continue...\n";
    } else {
        echo "   Réponse complète: " . $sendRequestResult['response'] . "\n\n";
        exit;
    }
}
echo "\n";

// 4. Test d'annulation réussie
echo "❌ TEST ANNULATION RÉUSSIE\n";
echo "==========================\n";

$cancelRequestResult = testEndpoint('DELETE', '/users/friend-requests', [
    'target_user_id' => $targetUserId
], $token, 'Annuler la demande d\'ami');

$cancelRequestData = json_decode($cancelRequestResult['response'], true);
if (isset($cancelRequestData['success']) && $cancelRequestData['success']) {
    echo "   ✅ Demande d'ami annulée avec succès\n";
    echo "   📊 Request ID: " . $cancelRequestData['data']['requestId'] . "\n";
    echo "   📊 Sender ID: " . $cancelRequestData['data']['senderId'] . "\n";
    echo "   📊 Receiver ID: " . $cancelRequestData['data']['receiverId'] . "\n";
    echo "   📊 Statut: " . $cancelRequestData['data']['status'] . "\n";
    echo "   📊 Annulée le: " . $cancelRequestData['data']['cancelledAt'] . "\n";
} else {
    echo "   ❌ Échec de l'annulation\n";
    echo "   Erreur: " . ($cancelRequestData['error']['message'] ?? 'Erreur inconnue') . "\n";
    echo "   Code d'erreur: " . ($cancelRequestData['error']['code'] ?? 'N/A') . "\n";
}
echo "\n";

// 5. Test d'annulation d'une demande déjà annulée
echo "❌ TEST ANNULATION DEMANDE DÉJÀ ANNULÉE\n";
echo "========================================\n";

$cancelTwiceResult = testEndpoint('DELETE', '/users/friend-requests', [
    'target_user_id' => $targetUserId
], $token, 'Annuler une demande déjà annulée');

$cancelTwiceData = json_decode($cancelTwiceResult['response'], true);
if (isset($cancelTwiceData['success']) && !$cancelTwiceData['success']) {
    echo "   ✅ Erreur attendue: " . $cancelTwiceData['error']['message'] . "\n";
    echo "   📊 Code d'erreur: " . $cancelTwiceData['error']['code'] . "\n";
} else {
    echo "   ❌ Erreur non détectée\n";
}
echo "\n";

// 6. Test d'annulation sans target_user_id
echo "❌ TEST ANNULATION SANS TARGET_USER_ID\n";
echo "======================================\n";

$cancelNoTargetResult = testEndpoint('DELETE', '/users/friend-requests', [], $token, 'Annuler sans target_user_id');

$cancelNoTargetData = json_decode($cancelNoTargetResult['response'], true);
if (isset($cancelNoTargetData['success']) && !$cancelNoTargetData['success']) {
    echo "   ✅ Erreur attendue: " . $cancelNoTargetData['error']['message'] . "\n";
    echo "   📊 Code d'erreur: " . $cancelNoTargetData['error']['code'] . "\n";
} else {
    echo "   ❌ Erreur non détectée\n";
}
echo "\n";

// 7. Test d'annulation avec un ID invalide
echo "❌ TEST ANNULATION AVEC ID INVALIDE\n";
echo "===================================\n";

$cancelInvalidResult = testEndpoint('DELETE', '/users/friend-requests', [
    'target_user_id' => 'invalid-uuid'
], $token, 'Annuler avec un ID invalide');

$cancelInvalidData = json_decode($cancelInvalidResult['response'], true);
if (isset($cancelInvalidData['success']) && !$cancelInvalidData['success']) {
    echo "   ✅ Erreur attendue: " . $cancelInvalidData['error']['message'] . "\n";
    echo "   📊 Code d'erreur: " . $cancelInvalidData['error']['code'] . "\n";
} else {
    echo "   ❌ Erreur non détectée\n";
}

echo "\n=== FIN DU TEST ===\n";
