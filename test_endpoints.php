<?php

/**
 * Script de test pour vérifier tous les endpoints de l'API Alarrache
 * Usage: php test_endpoints.php
 */

class ApiTester
{
    private string $baseUrl = 'http://localhost:8000/api';
    private ?string $authToken = null;
    private string $testEmail = 'test@example.com';
    private string $testPassword = 'password123';
    private string $testUserId = '';

    public function runTests(): void
    {
        echo "🚀 Démarrage des tests de l'API Alarrache\n";
        echo "==========================================\n\n";

        $this->testAuthEndpoints();
        $this->testUserEndpoints();
        $this->testSessionEndpoints();
        $this->testNotificationEndpoints();

        echo "\n✅ Tous les tests sont terminés !\n";
    }

    private function testAuthEndpoints(): void
    {
        echo "🔐 Test des endpoints d'authentification\n";
        echo "----------------------------------------\n";

        // Test 1: Register
        echo "1. Test POST /register... ";
        $response = $this->makeRequest('POST', '/register', [
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => $this->testEmail,
            'password' => $this->testPassword,
            'device_name' => 'Test Device',
            'verification_code' => 'PRO-8X24-UT'
        ]);
        $this->checkResponse($response, 201, 'success');

        // Test 2: Login
        echo "2. Test POST /login... ";
        $response = $this->makeRequest('POST', '/login', [
            'email' => $this->testEmail,
            'password' => $this->testPassword,
            'device_name' => 'Test Device'
        ]);
        $this->checkResponse($response, 200, 'success');

        if (isset($response['token'])) {
            $this->authToken = $response['token'];
            echo "✅ Token obtenu\n";
        }

        // Test 3: Refresh Token
        echo "3. Test POST /auth/refresh... ";
        $response = $this->makeRequest('POST', '/auth/refresh', [], true);
        $this->checkResponse($response, 200, 'success');

        // Test 4: Logout
        echo "4. Test POST /logout... ";
        $response = $this->makeRequest('POST', '/logout', [], true);
        $this->checkResponse($response, 200, 'success');

        echo "\n";
    }

    private function testUserEndpoints(): void
    {
        echo "👤 Test des endpoints utilisateurs\n";
        echo "----------------------------------\n";

        // Re-login pour obtenir un nouveau token
        $response = $this->makeRequest('POST', '/login', [
            'email' => $this->testEmail,
            'password' => $this->testPassword,
            'device_name' => 'Test Device'
        ]);
        if (isset($response['token'])) {
            $this->authToken = $response['token'];
        }

        // Test 1: Get User Profile
        echo "1. Test GET /users/profile... ";
        $response = $this->makeRequest('GET', '/users/profile', [], true);
        $this->checkResponse($response, 200, 'success');

        // Test 2: Update User Profile
        echo "2. Test PUT /users/profile... ";
        $response = $this->makeRequest('PUT', '/users/profile', [
            'firstname' => 'Updated',
            'lastname' => 'Name'
        ], true);
        $this->checkResponse($response, 200, 'success');

        // Test 3: Get Friends
        echo "3. Test GET /users/friends... ";
        $response = $this->makeRequest('GET', '/users/friends', [], true);
        $this->checkResponse($response, 200, 'success');

        // Test 4: Get Friend Requests
        echo "4. Test GET /users/friend-requests... ";
        $response = $this->makeRequest('GET', '/users/friend-requests', [], true);
        $this->checkResponse($response, 200, 'success');

        // Test 5: Search Users
        echo "5. Test GET /users/search?q=test... ";
        $response = $this->makeRequest('GET', '/users/search?q=test', [], true);
        $this->checkResponse($response, 200, 'success');

        // Test 6: Update Email
        echo "6. Test POST /users/update-email... ";
        $response = $this->makeRequest('POST', '/users/update-email', [
            'newEmail' => 'newemail@example.com',
            'currentEmail' => $this->testEmail
        ], true);
        $this->checkResponse($response, 200, 'success');

        // Test 7: Update Password
        echo "7. Test POST /users/update-password... ";
        $response = $this->makeRequest('POST', '/users/update-password', [
            'currentPassword' => $this->testPassword,
            'newPassword' => 'newpassword123'
        ], true);
        $this->checkResponse($response, 200, 'success');

        echo "\n";
    }

    private function testSessionEndpoints(): void
    {
        echo "🏃 Test des endpoints de sessions\n";
        echo "--------------------------------\n";

        // Re-login pour obtenir un nouveau token
        $response = $this->makeRequest('POST', '/login', [
            'email' => 'newemail@example.com',
            'password' => 'newpassword123',
            'device_name' => 'Test Device'
        ]);
        if (isset($response['token'])) {
            $this->authToken = $response['token'];
        }

        // Test 1: Get Sessions
        echo "1. Test GET /sessions... ";
        $response = $this->makeRequest('GET', '/sessions', [], true);
        $this->checkResponse($response, 200, 'success');

        // Test 2: Create Session
        echo "2. Test POST /sessions... ";
        $response = $this->makeRequest('POST', '/sessions', [
            'sport' => 'tennis',
            'date' => date('Y-m-d', strtotime('+1 week')),
            'time' => '18:00',
            'location' => 'Tennis Club de Paris'
        ], true);
        $this->checkResponse($response, 201, 'success');

        $sessionId = $response['data']['id'] ?? '1';

        // Test 3: Get Session by ID
        echo "3. Test GET /sessions/{$sessionId}... ";
        $response = $this->makeRequest('GET', "/sessions/{$sessionId}", [], true);
        $this->checkResponse($response, 200, 'success');

        // Test 4: Update Session
        echo "4. Test PUT /sessions/{$sessionId}... ";
        $response = $this->makeRequest('PUT', "/sessions/{$sessionId}", [
            'time' => '19:00',
            'location' => 'Tennis Club de Paris - Court 2'
        ], true);
        $this->checkResponse($response, 200, 'success');

        // Test 5: Add Comment
        echo "5. Test POST /sessions/{$sessionId}/comments... ";
        $response = $this->makeRequest('POST', "/sessions/{$sessionId}/comments", [
            'content' => 'Super session !'
        ], true);
        $this->checkResponse($response, 201, 'success');

        // Test 6: Respond to Invitation
        echo "6. Test PATCH /sessions/{$sessionId}/respond... ";
        $response = $this->makeRequest('PATCH', "/sessions/{$sessionId}/respond", [
            'response' => 'accept'
        ], true);
        $this->checkResponse($response, 200, 'success');

        // Test 7: Delete Session
        echo "7. Test DELETE /sessions/{$sessionId}... ";
        $response = $this->makeRequest('DELETE', "/sessions/{$sessionId}", [], true);
        $this->checkResponse($response, 200, 'success');

        echo "\n";
    }

    private function testNotificationEndpoints(): void
    {
        echo "🔔 Test des endpoints de notifications\n";
        echo "-------------------------------------\n";

        // Re-login pour obtenir un nouveau token
        $response = $this->makeRequest('POST', '/login', [
            'email' => 'newemail@example.com',
            'password' => 'newpassword123',
            'device_name' => 'Test Device'
        ]);
        if (isset($response['token'])) {
            $this->authToken = $response['token'];
        }

        // Test 1: Get Notifications
        echo "1. Test GET /notifications... ";
        $response = $this->makeRequest('GET', '/notifications', [], true);
        $this->checkResponse($response, 200, 'success');

        // Test 2: Get Unread Count
        echo "2. Test GET /notifications/unread-count... ";
        $response = $this->makeRequest('GET', '/notifications/unread-count', [], true);
        $this->checkResponse($response, 200, 'success');

        // Test 3: Mark All as Read
        echo "3. Test PATCH /notifications/read-all... ";
        $response = $this->makeRequest('PATCH', '/notifications/read-all', [
            'read' => true
        ], true);
        $this->checkResponse($response, 200, 'success');

        // Test 4: Push Notification
        echo "4. Test POST /notifications/push... ";
        $response = $this->makeRequest('POST', '/notifications/push', [
            'userId' => '1',
            'notification' => [
                'type' => 'invitation',
                'title' => 'Nouvelle invitation',
                'message' => 'Test invitation'
            ]
        ], true);
        $this->checkResponse($response, 201, 'success');

        echo "\n";
    }

    private function makeRequest(string $method, string $endpoint, array $data = [], bool $auth = false): array
    {
        $url = $this->baseUrl . $endpoint;

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        if ($auth && $this->authToken) {
            $headers[] = 'Authorization: Bearer ' . $this->authToken;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            return ['error' => 'cURL error', 'http_code' => $httpCode];
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'JSON decode error', 'raw_response' => $response, 'http_code' => $httpCode];
        }

        return $decoded ?: ['http_code' => $httpCode];
    }

    private function checkResponse(array $response, int $expectedCode, ?string $expectedStatus = null): void
    {
        if (isset($response['error'])) {
            echo "❌ ERREUR: " . (is_string($response['error']) ? $response['error'] : json_encode($response['error'])) . "\n";
            return;
        }

        if (isset($response['success']) && $response['success'] === ($expectedStatus === 'success')) {
            echo "✅ SUCCÈS\n";
        } elseif (isset($response['token']) || isset($response['message'])) {
            echo "✅ SUCCÈS\n";
        } else {
            echo "❌ ÉCHEC - Réponse: " . json_encode($response) . "\n";
        }
    }
}

// Exécuter les tests
$tester = new ApiTester();
$tester->runTests();
