<?php

echo "=== TEST DE L'API ALARRACHE ===\n\n";

$baseUrl = 'http://localhost:8000/api';
$token = null;

function testEndpoint($method, $endpoint, $data = null, $token = null) {
    global $baseUrl;

    $url = $baseUrl . $endpoint;
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Test: $method $endpoint\n";
    echo "Code HTTP: $httpCode\n";
    echo "Réponse: " . substr($response, 0, 200) . (strlen($response) > 200 ? "..." : "") . "\n";
    echo "---\n\n";

    return ['code' => $httpCode, 'response' => $response];
}

// Test 1: Version (sans authentification)
echo "1. Test endpoint version (sans auth):\n";
testEndpoint('GET', '/version');

// Test 2: Login
echo "2. Test login:\n";
$loginResult = testEndpoint('POST', '/login', [
    'email' => 'test@example.com',
    'password' => 'password123',
    'device_name' => 'test-device'
]);

// Extraire le token de la réponse
$loginData = json_decode($loginResult['response'], true);
if (isset($loginData['token'])) {
    global $token;
    $token = $loginData['token'];
    echo "Token obtenu: " . substr($token, 0, 20) . "...\n\n";
}

// Test 3: Profile utilisateur (avec auth)
echo "3. Test profile utilisateur (avec auth):\n";
testEndpoint('GET', '/users/profile', null, $token);

// Test 4: Sessions (avec auth)
echo "4. Test sessions (avec auth):\n";
testEndpoint('GET', '/sessions', null, $token);

// Test 5: Créer une session
echo "5. Test création de session:\n";
testEndpoint('POST', '/sessions', [
    'sport' => 'tennis',
    'date' => date('Y-m-d', strtotime('+1 week')),
    'time' => '18:00',
    'location' => 'Tennis Club de Paris'
], $token);

// Test 6: Notifications
echo "6. Test notifications:\n";
testEndpoint('GET', '/notifications', null, $token);

echo "=== FIN DES TESTS ===\n";
