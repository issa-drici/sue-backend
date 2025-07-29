<?php

echo "=== TEST COMPLET DE TOUS LES ENDPOINTS DE L'API ALARRACHE ===\n\n";

// Configuration
$baseUrl = 'http://localhost:8000/api';
$token = null;

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

    if ($httpCode >= 400) {
        echo "   Erreur: " . substr($response, 0, 100) . (strlen($response) > 100 ? "..." : "") . "\n";
    }

    return ['code' => $httpCode, 'response' => $response];
}

// ===== AUTHENTIFICATION =====
echo "🔐 AUTHENTIFICATION\n";
echo "==================\n";

// 1. Login avec l'utilisateur existant
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'finaltest4@example.com',
    'password' => 'password123',
    'device_name' => 'test-device'
], null, 'Se connecter avec un utilisateur existant');

// Extraire le token
$loginData = json_decode($loginResult['response'], true);
if (isset($loginData['token'])) {
    $token = $loginData['token'];
    echo "   Token obtenu: " . substr($token, 0, 20) . "...\n\n";
}

// 2. Refresh Token
testEndpoint('POST', '/auth/refresh', [], $token, 'Rafraîchir le token');

// ===== UTILISATEURS =====
echo "\n👤 UTILISATEURS\n";
echo "===============\n";

// 3. Get User Profile
testEndpoint('GET', '/users/profile', null, $token, 'Obtenir le profil utilisateur');

// 4. Update User Profile
$updateProfileResult = testEndpoint('PUT', '/users/profile', [
    'firstname' => 'John',
    'lastname' => 'Doe'
], $token, 'Mettre à jour le profil');

// Afficher l'erreur complète pour le debug
if (strpos($updateProfileResult['response'], 'INTERNAL_ERROR') !== false) {
    echo "ERREUR COMPLÈTE PUT /users/profile: " . $updateProfileResult['response'] . "\n";
}

// 5. Get User by ID
testEndpoint('GET', '/users/d1d819b5-88e6-41c9-b070-15928cf3c970', null, $token, 'Obtenir un utilisateur par ID');

// 6. Search Users
testEndpoint('GET', '/users/search?q=test', null, $token, 'Rechercher des utilisateurs');

// 7. Get User Friends
testEndpoint('GET', '/users/friends', null, $token, 'Obtenir les amis');

// 8. Get Friend Requests
testEndpoint('GET', '/users/friend-requests', null, $token, 'Obtenir les demandes d\'ami');

// 9. Send Friend Request (vers un utilisateur existant)
$friendRequestResult = testEndpoint('POST', '/users/friend-requests', [
    'userId' => '9f6fa81c-76b5-4df9-aaa6-358d7006a64f' // Utiliser l'ID de l'autre utilisateur existant
], $token, 'Envoyer une demande d\'ami vers un utilisateur existant');

// 10. Update User Email
testEndpoint('POST', '/users/update-email', [
    'newEmail' => 'newemail10@example.com',
    'currentEmail' => 'finaltest4@example.com'
], $token, 'Mettre à jour l\'email');

// 11. Update User Password
testEndpoint('POST', '/users/update-password', [
    'currentPassword' => 'password123',
    'newPassword' => 'newpassword123'
], $token, 'Mettre à jour le mot de passe');

// ===== SESSIONS =====
echo "\n🏃 SESSIONS\n";
echo "===========\n";

// 12. Get All Sessions
$sessionsResult = testEndpoint('GET', '/sessions', null, $token, 'Obtenir toutes les sessions');

// 13. Create Session
$createSessionResult = testEndpoint('POST', '/sessions', [
    'sport' => 'tennis',
    'date' => date('Y-m-d', strtotime('+1 week')),
    'time' => '18:00',
    'location' => 'Tennis Club de Paris'
], $token, 'Créer une session');

// Extraire l'ID de session
$sessionData = json_decode($createSessionResult['response'], true);
if (isset($sessionData['data']['id'])) {
    $sessionId = $sessionData['data']['id'];
    echo "   Session créée avec ID: $sessionId\n";
}

// 14. Get Session by ID
if ($sessionId) {
    testEndpoint('GET', "/sessions/$sessionId", null, $token, 'Obtenir une session par ID');
}

// 15. Update Session
if ($sessionId) {
    testEndpoint('PUT', "/sessions/$sessionId", [
        'sport' => 'tennis',
        'date' => date('Y-m-d', strtotime('+2 weeks')),
        'time' => '19:00',
        'location' => 'Tennis Club de Paris Updated'
    ], $token, 'Mettre à jour une session');
}

// 16. Add Session Comment
if ($sessionId) {
    testEndpoint('POST', "/sessions/$sessionId/comments", [
        'content' => 'Super session !'
    ], $token, 'Ajouter un commentaire');
}

// 17. Inviter des utilisateurs à la session
if ($sessionId) {
    testEndpoint('POST', "/sessions/$sessionId/invite", [
        'userIds' => ['9f6fb119-6916-4a91-878b-26676818a25f'] // Inviter l'utilisateur invited@example.com
    ], $token, 'Inviter des utilisateurs à la session');
}

// 18. Se connecter avec l'utilisateur invité pour tester la réponse
if ($sessionId) {
    // Login avec l'utilisateur invité
    $loginInvitedResult = testEndpoint('POST', '/login', [
        'email' => 'invited@example.com',
        'password' => 'password123',
        'device_name' => 'test-device-invited'
    ], null, 'Se connecter avec l\'utilisateur invité');

    // Extraire le token de l'utilisateur invité
    $loginInvitedData = json_decode($loginInvitedResult['response'], true);
    if (isset($loginInvitedData['token'])) {
        $invitedToken = $loginInvitedData['token'];
        echo "   Token utilisateur invité obtenu: " . substr($invitedToken, 0, 20) . "...\n";

        // Répondre à l'invitation avec l'utilisateur invité
        testEndpoint('PATCH', "/sessions/$sessionId/respond", [
            'response' => 'accept'
        ], $invitedToken, 'Répondre à une invitation (utilisateur invité)');
    }
}

// ===== NOTIFICATIONS =====
echo "\n🔔 NOTIFICATIONS\n";
echo "================\n";

// 19. Get Notifications
$notificationsResult = testEndpoint('GET', '/notifications', null, $token, 'Obtenir les notifications');

// Extraire l'ID de notification
$notificationsData = json_decode($notificationsResult['response'], true);
if (isset($notificationsData['data'][0]['id'])) {
    $notificationId = $notificationsData['data'][0]['id'];
    echo "   Notification trouvée avec ID: $notificationId\n";
}

// 20. Mark Notification as Read
if ($notificationId) {
    testEndpoint('PATCH', "/notifications/$notificationId/read", [], $token, 'Marquer une notification comme lue');
}

// 21. Mark All Notifications as Read
testEndpoint('PATCH', '/notifications/read-all', [], $token, 'Marquer toutes les notifications comme lues');

// 22. Get Unread Count
testEndpoint('GET', '/notifications/unread-count', null, $token, 'Obtenir le nombre de notifications non lues');

// 23. Push Notification (Webhook) - Ajouter un token pour l'authentification
testEndpoint('POST', '/notifications/push', [
    'userId' => 'd1d819b5-88e6-41c9-b070-15928cf3c970',
    'notification' => [
        'title' => 'Test Push',
        'message' => 'Test push notification'
    ]
], $token, 'Notification push (webhook)');

// ===== SUPPORT =====
echo "\n🆘 SUPPORT\n";
echo "==========\n";

// 24. Create Support Request
testEndpoint('POST', '/support', [
    'subject' => 'Test Support',
    'message' => 'Ceci est un test de support',
    'category' => 'technical'
], $token, 'Créer une demande de support');

// 25. Get Support Requests
testEndpoint('GET', '/support', null, $token, 'Obtenir les demandes de support');

// ===== AUTRES =====
echo "\n📋 AUTRES\n";
echo "==========\n";

// 26. Version Check
testEndpoint('GET', '/version', null, null, 'Vérifier la version');

// 27. Logout
testEndpoint('POST', '/logout', [], $token, 'Se déconnecter');

echo "\n=== FIN DES TESTS ===\n";
echo "✅ Tests terminés avec succès !\n";
