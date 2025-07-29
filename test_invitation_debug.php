<?php

require_once 'vendor/autoload.php';

// Configuration
$baseUrl = 'http://localhost:8000';
$email = 'test@example.com';
$password = 'password';

echo "🔍 Test du processus d'invitation\n";
echo "===============================\n\n";

// 1. Connexion
echo "1. 🔐 Connexion...\n";
$loginData = [
    'email' => $email,
    'password' => $password,
    'device_name' => 'test-device'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Erreur de connexion: HTTP $httpCode\n";
    exit(1);
}

$loginResult = json_decode($response, true);
$token = $loginResult['token'] ?? null;
$userId = $loginResult['user']['id'] ?? null;

echo "✅ Connexion réussie\n";
echo "User ID: $userId\n\n";

// 2. Créer un utilisateur pour l'invitation
echo "2. 👤 Création d'un utilisateur pour l'invitation...\n";
$newUserData = [
    'firstname' => 'Test',
    'lastname' => 'User',
    'email' => 'test.user.' . time() . '@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'device_name' => 'test-device-2'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/register');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($newUserData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$newUserResult = json_decode($response, true);
$newUserId = $newUserResult['user']['id'] ?? null;
$newUserToken = $newUserResult['token'] ?? null;

if ($httpCode === 201 && $newUserId) {
    echo "✅ Nouvel utilisateur créé: $newUserId\n\n";
} else {
    echo "❌ Erreur création utilisateur: $response\n\n";
    exit(1);
}

// 3. Enregistrer un token push pour le nouvel utilisateur
echo "3. 📱 Enregistrement token push...\n";
$pushData = [
    'token' => 'ExponentPushToken[TestTokenForInvitation]',
    'platform' => 'expo'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/push-tokens');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($pushData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $newUserToken,
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Réponse: $response\n\n";

// 4. Créer une session
echo "4. 🏃‍♂️ Création d'une session...\n";
$sessionData = [
    'sport' => 'football',
    'date' => '2026-12-25',
    'time' => '15:00',
    'location' => 'Stade Test',
    'description' => 'Test d\'invitation',
    'maxParticipants' => 5
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/sessions');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sessionData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Réponse: $response\n";

if ($httpCode !== 201) {
    echo "❌ Erreur création session\n\n";
    exit(1);
}

$sessionResult = json_decode($response, true);
$sessionId = $sessionResult['data']['id'] ?? null;

if (!$sessionId) {
    echo "❌ Session ID non trouvé\n\n";
    exit(1);
}

echo "✅ Session créée: $sessionId\n\n";

// 5. Inviter l'utilisateur
echo "5. 📨 Invitation de l'utilisateur...\n";
$inviteData = [
    'userIds' => [$newUserId]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . "/api/sessions/$sessionId/invite");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($inviteData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Réponse: $response\n\n";

// 6. Vérifier les notifications pour l'utilisateur invité
echo "6. 🔔 Vérification des notifications pour l'utilisateur invité...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/notifications');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $newUserToken,
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$notificationsResult = json_decode($response, true);

if ($httpCode === 200 && !empty($notificationsResult['data'])) {
    echo "✅ Notifications trouvées: " . count($notificationsResult['data']) . "\n";

    $invitationNotifications = array_filter($notificationsResult['data'], function($notification) use ($sessionId) {
        return isset($notification['type']) && $notification['type'] === 'invitation' && isset($notification['session_id']) && $notification['session_id'] === $sessionId;
    });

    if (!empty($invitationNotifications)) {
        echo "✅ Notifications d'invitation trouvées: " . count($invitationNotifications) . "\n";
        foreach ($invitationNotifications as $notification) {
            echo "   - ID: {$notification['id']}\n";
            echo "     Titre: {$notification['title']}\n";
            echo "     Message: {$notification['message']}\n";
            echo "     Push envoyé: " . (isset($notification['push_sent']) && $notification['push_sent'] ? 'Oui' : 'Non') . "\n";
            if (isset($notification['push_sent']) && $notification['push_sent'] && isset($notification['push_sent_at'])) {
                echo "     Push envoyé à: {$notification['push_sent_at']}\n";
            }
            if (isset($notification['push_data'])) {
                echo "     Données push: " . json_encode($notification['push_data']) . "\n";
            }
            echo "\n";
        }
    } else {
        echo "❌ Aucune notification d'invitation trouvée\n\n";
    }
} else {
    echo "❌ Aucune notification trouvée\n\n";
}

echo "�� Test terminé !\n";
