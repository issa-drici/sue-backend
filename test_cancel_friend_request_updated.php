<?php

// Test pour l'endpoint d'annulation de demande d'ami (version mise à jour)
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

echo "=== TEST ENDPOINT ANNULATION DEMANDE D'AMI (MIS À JOUR) ===\n\n";

// 1. Créer un utilisateur principal
echo "🔐 CRÉATION UTILISATEUR PRINCIPAL\n";
echo "==================================\n";

$createMainUserResult = testEndpoint('POST', '/register', [
    'email' => 'main.cancel2@example.com',
    'password' => 'password123',
    'firstname' => 'Main',
    'lastname' => 'Cancel2',
    'device_name' => 'test-device'
], null, 'Créer l\'utilisateur principal');

$mainUserData = json_decode($createMainUserResult['response'], true);
$mainUserId = $mainUserData['user']['id'] ?? null;

// 2. Créer un utilisateur cible
echo "🔐 CRÉATION UTILISATEUR CIBLE\n";
echo "=============================\n";

$createTargetUserResult = testEndpoint('POST', '/register', [
    'email' => 'target.cancel2@example.com',
    'password' => 'password123',
    'firstname' => 'Target',
    'lastname' => 'Cancel2',
    'device_name' => 'test-device'
], null, 'Créer l\'utilisateur cible');

$targetUserData = json_decode($createTargetUserResult['response'], true);
$targetUserId = $targetUserData['user']['id'] ?? null;

// 3. Créer un utilisateur tiers
echo "🔐 CRÉATION UTILISATEUR TIERS\n";
echo "=============================\n";

$createThirdUserResult = testEndpoint('POST', '/register', [
    'email' => 'third.cancel2@example.com',
    'password' => 'password123',
    'firstname' => 'Third',
    'lastname' => 'Cancel2',
    'device_name' => 'test-device'
], null, 'Créer l\'utilisateur tiers');

$thirdUserData = json_decode($createThirdUserResult['response'], true);
$thirdUserId = $thirdUserData['user']['id'] ?? null;

// 4. Login avec l'utilisateur principal
$loginMainResult = testEndpoint('POST', '/login', [
    'email' => 'main.cancel2@example.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter avec l\'utilisateur principal');

$loginMainData = json_decode($loginMainResult['response'], true);
$mainToken = $loginMainData['token'] ?? null;

if ($mainToken) {
    echo "   Token principal obtenu: " . substr($mainToken, 0, 20) . "...\n";
    echo "   ID utilisateur principal: $mainUserId\n\n";
}

// 5. Login avec l'utilisateur tiers
$loginThirdResult = testEndpoint('POST', '/login', [
    'email' => 'third.cancel2@example.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter avec l\'utilisateur tiers');

$loginThirdData = json_decode($loginThirdResult['response'], true);
$thirdToken = $loginThirdData['token'] ?? null;

// 6. Envoyer une demande d'ami
echo "🤝 ENVOI DEMANDE D'AMI\n";
echo "======================\n";

$sendRequestResult = testEndpoint('POST', '/users/friend-requests', [
    'userId' => $targetUserId
], $mainToken, 'Envoyer une demande d\'ami');

$sendRequestData = json_decode($sendRequestResult['response'], true);
if (isset($sendRequestData['success']) && $sendRequestData['success']) {
    echo "   ✅ Demande d'ami envoyée avec succès\n";
    echo "   📊 De: $mainUserId vers: $targetUserId\n";
} else {
    echo "   ❌ Échec de l'envoi de la demande d'ami\n";
    echo "   Erreur: " . ($sendRequestData['error']['message'] ?? 'Erreur inconnue') . "\n";
}
echo "\n";

// 7. Test d'annulation réussie
echo "❌ TEST ANNULATION RÉUSSIE\n";
echo "==========================\n";

$cancelRequestResult = testEndpoint('DELETE', '/users/friend-requests', [
    'target_user_id' => $targetUserId
], $mainToken, 'Annuler la demande d\'ami');

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
}
echo "\n";

// 8. Test d'annulation sans target_user_id
echo "❌ TEST ANNULATION SANS TARGET_USER_ID\n";
echo "======================================\n";

$cancelNoTargetResult = testEndpoint('DELETE', '/users/friend-requests', [], $mainToken, 'Annuler sans target_user_id');

$cancelNoTargetData = json_decode($cancelNoTargetResult['response'], true);
if (isset($cancelNoTargetData['success']) && !$cancelNoTargetData['success']) {
    echo "   ✅ Erreur attendue: " . $cancelNoTargetData['error']['message'] . "\n";
    echo "   📊 Code d'erreur: " . $cancelNoTargetData['error']['code'] . "\n";
} else {
    echo "   ❌ Erreur non détectée\n";
}
echo "\n";

// 9. Envoyer une nouvelle demande pour tester l'autorisation
echo "🤝 ENVOI NOUVELLE DEMANDE POUR TEST AUTORISATION\n";
echo "================================================\n";

$sendNewRequestResult = testEndpoint('POST', '/users/friend-requests', [
    'userId' => $thirdUserId
], $mainToken, 'Envoyer une nouvelle demande d\'ami');

$sendNewRequestData = json_decode($sendNewRequestResult['response'], true);
if (isset($sendNewRequestData['success']) && $sendNewRequestData['success']) {
    echo "   ✅ Nouvelle demande d'ami envoyée\n";
    echo "   📊 De: $mainUserId vers: $thirdUserId\n";
} else {
    echo "   ❌ Échec de l'envoi de la nouvelle demande\n";
}
echo "\n";

// 10. Test d'annulation par un autre utilisateur (non autorisé)
echo "❌ TEST ANNULATION PAR AUTRE UTILISATEUR\n";
echo "========================================\n";

$cancelUnauthorizedResult = testEndpoint('DELETE', '/users/friend-requests', [
    'target_user_id' => $mainUserId
], $thirdToken, 'Annuler une demande d\'un autre utilisateur');

$cancelUnauthorizedData = json_decode($cancelUnauthorizedResult['response'], true);
if (isset($cancelUnauthorizedData['success']) && !$cancelUnauthorizedData['success']) {
    echo "   ✅ Erreur d'autorisation attendue: " . $cancelUnauthorizedData['error']['message'] . "\n";
    echo "   📊 Code d'erreur: " . $cancelUnauthorizedData['error']['code'] . "\n";
} else {
    echo "   ❌ Erreur d'autorisation non détectée\n";
}
echo "\n";

// 11. Test d'annulation d'une demande inexistante
echo "❌ TEST ANNULATION DEMANDE INEXISTANTE\n";
echo "======================================\n";

$cancelNonExistentResult = testEndpoint('DELETE', '/users/friend-requests', [
    'target_user_id' => 'non-existent-user-id'
], $mainToken, 'Annuler une demande inexistante');

$cancelNonExistentData = json_decode($cancelNonExistentResult['response'], true);
if (isset($cancelNonExistentData['success']) && !$cancelNonExistentData['success']) {
    echo "   ✅ Erreur attendue: " . $cancelNonExistentData['error']['message'] . "\n";
    echo "   📊 Code d'erreur: " . $cancelNonExistentData['error']['code'] . "\n";
} else {
    echo "   ❌ Erreur non détectée\n";
}
echo "\n";

// 12. Test d'annulation d'une demande déjà annulée
echo "❌ TEST ANNULATION DEMANDE DÉJÀ ANNULÉE\n";
echo "========================================\n";

$cancelTwiceResult = testEndpoint('DELETE', '/users/friend-requests', [
    'target_user_id' => $thirdUserId
], $mainToken, 'Annuler une demande déjà annulée');

$cancelTwiceData = json_decode($cancelTwiceResult['response'], true);
if (isset($cancelTwiceData['success']) && !$cancelTwiceData['success']) {
    echo "   ✅ Erreur attendue: " . $cancelTwiceData['error']['message'] . "\n";
    echo "   📊 Code d'erreur: " . $cancelTwiceData['error']['code'] . "\n";
} else {
    echo "   ❌ Erreur non détectée\n";
}

echo "\n=== FIN DU TEST ===\n";
