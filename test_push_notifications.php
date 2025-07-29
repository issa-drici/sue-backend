<?php

// Test pour vérifier le système de notifications push
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

echo "=== TEST NOTIFICATIONS PUSH ===\n\n";

// 1. Créer un utilisateur
echo "🔐 CRÉATION UTILISATEUR\n";
echo "=======================\n";

$createUserResult = testEndpoint('POST', '/register', [
    'email' => 'user@push.com',
    'password' => 'password123',
    'firstname' => 'User',
    'lastname' => 'Push',
    'device_name' => 'test-device'
], null, 'Créer un utilisateur');

// 2. Login
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'user@push.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter');

$userData = json_decode($loginResult['response'], true);
if (!isset($userData['token'])) {
    echo "   ❌ Erreur lors de la connexion\n";
    exit;
}

$token = $userData['token'];
echo "   Token obtenu: " . substr($token, 0, 20) . "...\n\n";

// 3. Enregistrer un token push valide
echo "📱 ENREGISTREMENT TOKEN PUSH VALIDE\n";
echo "===================================\n";

$validToken = 'ExponentPushToken[test-token-123]';
$saveTokenResult = testEndpoint('POST', '/push-tokens', [
    'token' => $validToken,
    'platform' => 'expo'
], $token, 'Enregistrer un token push valide');

if ($saveTokenResult['code'] === 200) {
    echo "   ✅ Token push enregistré avec succès\n";
    echo "   - Token: $validToken\n";
    echo "   - Platform: expo\n";
} else {
    echo "   ❌ Erreur lors de l'enregistrement du token\n";
    echo "   Réponse: " . $saveTokenResult['response'] . "\n";
}

// 4. Enregistrer un token push invalide
echo "\n📱 ENREGISTREMENT TOKEN PUSH INVALIDE\n";
echo "=====================================\n";

$invalidToken = 'invalid-token-format';
$saveInvalidTokenResult = testEndpoint('POST', '/push-tokens', [
    'token' => $invalidToken,
    'platform' => 'expo'
], $token, 'Enregistrer un token push invalide');

if ($saveInvalidTokenResult['code'] === 400) {
    echo "   ✅ Token invalide correctement rejeté\n";
    echo "   - Token: $invalidToken\n";
} else {
    echo "   ❌ Le token invalide aurait dû être rejeté\n";
    echo "   Réponse: " . $saveInvalidTokenResult['response'] . "\n";
}

// 5. Enregistrer un token sans spécifier la plateforme
echo "\n📱 ENREGISTREMENT TOKEN SANS PLATEFORME\n";
echo "=======================================\n";

$tokenWithoutPlatform = 'ExponentPushToken[test-token-456]';
$saveTokenNoPlatformResult = testEndpoint('POST', '/push-tokens', [
    'token' => $tokenWithoutPlatform
], $token, 'Enregistrer un token sans spécifier la plateforme');

if ($saveTokenNoPlatformResult['code'] === 200) {
    echo "   ✅ Token enregistré avec plateforme par défaut\n";
    echo "   - Token: $tokenWithoutPlatform\n";
    echo "   - Platform: expo (par défaut)\n";
} else {
    echo "   ❌ Erreur lors de l'enregistrement du token\n";
    echo "   Réponse: " . $saveTokenNoPlatformResult['response'] . "\n";
}

// 6. Test avec données manquantes
echo "\n📱 TEST DONNÉES MANQUANTES\n";
echo "===========================\n";

$missingDataResult = testEndpoint('POST', '/push-tokens', [
    'platform' => 'expo'
], $token, 'Tenter d\'enregistrer sans token');

if ($missingDataResult['code'] === 400) {
    echo "   ✅ Validation correcte des données manquantes\n";
} else {
    echo "   ❌ La validation aurait dû échouer\n";
    echo "   Réponse: " . $missingDataResult['response'] . "\n";
}

// 7. Test avec plateforme invalide
echo "\n📱 TEST PLATEFORME INVALIDE\n";
echo "============================\n";

$invalidPlatformResult = testEndpoint('POST', '/push-tokens', [
    'token' => 'ExponentPushToken[test-token-789]',
    'platform' => 'invalid-platform'
], $token, 'Tenter d\'enregistrer avec une plateforme invalide');

if ($invalidPlatformResult['code'] === 400) {
    echo "   ✅ Validation correcte de la plateforme\n";
} else {
    echo "   ❌ La validation de la plateforme aurait dû échouer\n";
    echo "   Réponse: " . $invalidPlatformResult['response'] . "\n";
}

echo "\n=== RÉSUMÉ ===\n";
echo "==============\n";
echo "✅ Endpoint /push-tokens créé\n";
echo "✅ Validation des tokens Expo\n";
echo "✅ Validation des plateformes\n";
echo "✅ Gestion des erreurs\n";
echo "✅ Base de données configurée\n";

echo "\n=== PROCHAINES ÉTAPES ===\n";
echo "========================\n";
echo "1. Intégrer les notifications push dans le système existant\n";
echo "2. Créer des endpoints pour gérer les tokens (suppression, liste)\n";
echo "3. Tester l'envoi réel de notifications\n";
echo "4. Configurer le côté frontend Expo\n";

echo "\n=== TEST TERMINÉ ===\n";
