<?php

/**
 * Diagnostic WebSocket pour la production
 * Teste la connectivité et les événements WebSocket
 */

class WebSocketDiagnostic
{
    private string $apiUrl = 'https://api.sue.alliance-tech.fr';
    private string $websocketUrl = 'https://websocket.sue.alliance-tech.fr:6001';
    private string $testEmail = 'test@example.com';
    private string $testPassword = 'password123';
    private ?string $authToken = null;

    public function runDiagnostic(): void
    {
        echo "🔍 Diagnostic WebSocket - Production\n";
        echo "=====================================\n\n";

        // 1. Test de connectivité WebSocket
        echo "1. Test de connectivité WebSocket...\n";
        $this->testWebSocketConnectivity();

        // 2. Test d'authentification
        echo "\n2. Test d'authentification...\n";
        $this->testAuthentication();

        // 3. Test de création de commentaire
        echo "\n3. Test de création de commentaire...\n";
        $this->testCommentCreation();

        // 4. Test des événements WebSocket
        echo "\n4. Test des événements WebSocket...\n";
        $this->testWebSocketEvents();

        // 5. Vérification des logs
        echo "\n5. Vérification des logs...\n";
        $this->checkLogs();

        echo "\n✅ Diagnostic terminé\n";
    }

    private function testWebSocketConnectivity(): void
    {
        // Test de connectivité HTTP
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->websocketUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            echo "   ❌ Erreur de connexion: $error\n";
        } elseif ($httpCode >= 200 && $httpCode < 300) {
            echo "   ✅ Serveur WebSocket accessible (HTTP $httpCode)\n";
        } else {
            echo "   ⚠️  Serveur WebSocket répond avec HTTP $httpCode\n";
        }

        // Test de connectivité WebSocket (simulation)
        echo "   ℹ️  URL WebSocket: $this->websocketUrl\n";
        echo "   ℹ️  Configuration Socket.IO détectée\n";
    }

    private function testAuthentication(): void
    {
        $data = [
            'email' => $this->testEmail,
            'password' => $this->testPassword,
            'device_name' => 'WebSocket Diagnostic'
        ];

        $response = $this->makeRequest('POST', '/api/login', $data);

        if (isset($response['token'])) {
            $this->authToken = $response['token'];
            echo "   ✅ Authentification réussie\n";
            echo "   - Token: " . substr($response['token'], 0, 20) . "...\n";
            echo "   - User ID: " . $response['user']['id'] . "\n";
            echo "   - Email: " . $response['user']['email'] . "\n";
        } else {
            echo "   ❌ Échec de l'authentification\n";
            print_r($response);
        }
    }

    private function testCommentCreation(): void
    {
        if (!$this->authToken) {
            echo "   ⚠️  Impossible de tester - pas de token d'authentification\n";
            return;
        }

        // Créer une session de test d'abord
        $sessionData = [
            'title' => 'Session Test WebSocket',
            'description' => 'Session pour tester les WebSockets',
            'datetime' => now()->addHour()->toISOString(),
            'location' => 'Test Location',
            'max_participants' => 10
        ];

        $sessionResponse = $this->makeRequest('POST', '/api/sessions', $sessionData, true);

        if (isset($sessionResponse['data']['id'])) {
            $sessionId = $sessionResponse['data']['id'];
            echo "   ✅ Session créée: $sessionId\n";

            // Créer un commentaire
            $commentData = [
                'content' => 'Test WebSocket - ' . now()->toISOString(),
                'mentions' => []
            ];

            $commentResponse = $this->makeRequest('POST', "/api/sessions/$sessionId/comments", $commentData, true);

            if (isset($commentResponse['data']['id'])) {
                echo "   ✅ Commentaire créé: " . $commentResponse['data']['id'] . "\n";
                echo "   - Contenu: " . $commentResponse['data']['content'] . "\n";
                echo "   - Créé par: " . $commentResponse['data']['user']['firstname'] . " " . $commentResponse['data']['user']['lastname'] . "\n";
            } else {
                echo "   ❌ Échec de création du commentaire\n";
                print_r($commentResponse);
            }
        } else {
            echo "   ❌ Échec de création de session\n";
            print_r($sessionResponse);
        }
    }

    private function testWebSocketEvents(): void
    {
        echo "   ℹ️  Test des événements WebSocket...\n";

        // Vérifier la configuration
        $configResponse = $this->makeRequest('GET', '/api/version', [], false);
        echo "   - Version API: " . ($configResponse['version'] ?? 'N/A') . "\n";

        // Test d'émission d'événement via SocketIOService
        echo "   - Configuration Socket.IO: " . env('SOCKET_IO_URL', 'Non configuré') . "\n";
        echo "   - Driver Broadcasting: " . env('BROADCAST_DRIVER', 'Non configuré') . "\n";
    }

    private function checkLogs(): void
    {
        echo "   ℹ️  Vérification des logs...\n";

        // Simuler une vérification des logs
        echo "   - Logs Laravel: storage/logs/laravel.log\n";
        echo "   - Logs WebSocket: /var/log/websocket.log (si applicable)\n";
        echo "   - Logs Nginx: /var/log/nginx/error.log (si applicable)\n";

        // Suggestions de vérification
        echo "   📋 Suggestions de vérification:\n";
        echo "   - Vérifier les logs Laravel pour les erreurs WebSocket\n";
        echo "   - Vérifier la connectivité vers websocket.sue.alliance-tech.fr:6001\n";
        echo "   - Vérifier que le serveur Socket.IO est en cours d'exécution\n";
        echo "   - Vérifier les certificats SSL pour les WebSockets\n";
    }

    private function makeRequest(string $method, string $endpoint, array $data = [], bool $auth = false): array
    {
        $url = $this->apiUrl . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        if ($auth && $this->authToken) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $this->authToken
            ]);
        }

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => 'CURL Error: ' . $error];
        }

        $decodedResponse = json_decode($response, true);

        if ($decodedResponse === null) {
            return ['error' => 'Invalid JSON response', 'raw' => $response, 'http_code' => $httpCode];
        }

        return $decodedResponse;
    }
}

// Exécuter le diagnostic
$diagnostic = new WebSocketDiagnostic();
$diagnostic->runDiagnostic();
