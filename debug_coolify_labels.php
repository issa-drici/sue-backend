<?php

/**
 * Debug Coolify Labels - Vérification Complète
 *
 * Ce script vérifie l'état des labels et de la configuration
 */

class CoolifyLabelsDebug {
    private $baseUrl = 'https://websocket.sue.alliance-tech.fr:6001';
    private $httpUrl = 'http://websocket.sue.alliance-tech.fr:6001';

    public function run() {
        echo "🔍 Debug Coolify Labels - Vérification Complète\n";
        echo "===============================================\n\n";

        // Test 1: Connectivité de base
        $this->testBasicConnectivity();

        // Test 2: Vérifier les headers
        $this->testHeaders();

        // Test 3: Test Socket.IO
        $this->testSocketIO();

        // Test 4: Test Health Check
        $this->testHealthCheck();

        // Test 5: Diagnostic complet
        $this->diagnosticComplete();

        echo "\n✅ Debug Coolify Labels terminé\n";
    }

    private function testBasicConnectivity() {
        echo "1. Test Connectivité de Base...\n";

        // Test HTTP
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->httpUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_NOBODY => true,
            CURLOPT_HEADER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            echo "   ❌ Erreur HTTP: $error\n";
        } else {
            echo "   ✅ HTTP accessible (HTTP $httpCode)\n";
        }

        // Test HTTPS
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_NOBODY => true,
            CURLOPT_HEADER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            echo "   ❌ Erreur HTTPS: $error\n";
        } else {
            echo "   ✅ HTTPS accessible (HTTP $httpCode)\n";
        }
    }

    private function testHeaders() {
        echo "\n2. Test Headers...\n";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_HTTPHEADER => [
                'Host: websocket.sue.alliance-tech.fr'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            echo "   ❌ Erreur de headers: $error\n";
            return;
        }

        echo "   📊 Code HTTP: $httpCode\n";

        // Analyser les headers
        $headers = explode("\n", $response);
        foreach ($headers as $header) {
            if (stripos($header, 'server') !== false) {
                echo "   🖥️  Server: " . trim($header) . "\n";
            }
            if (stripos($header, 'x-powered-by') !== false) {
                echo "   ⚡ Powered by: " . trim($header) . "\n";
            }
            if (stripos($header, 'access-control') !== false) {
                echo "   🌐 CORS: " . trim($header) . "\n";
            }
        }
    }

    private function testSocketIO() {
        echo "\n3. Test Socket.IO...\n";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '/socket.io/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'Host: websocket.sue.alliance-tech.fr',
                'Accept: */*'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            echo "   ❌ Erreur Socket.IO: $error\n";
            return;
        }

        if ($httpCode === 200 || $httpCode === 404) {
            echo "   ✅ Socket.IO endpoint accessible (HTTP $httpCode)\n";
        } else {
            echo "   ❌ Socket.IO endpoint inaccessible (HTTP $httpCode)\n";
        }
    }

    private function testHealthCheck() {
        echo "\n4. Test Health Check...\n";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '/health',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'Host: websocket.sue.alliance-tech.fr',
                'Accept: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            echo "   ❌ Erreur Health Check: $error\n";
            return;
        }

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            echo "   ✅ Health check réussi\n";
            echo "   📊 Statut: " . ($data['status'] ?? 'N/A') . "\n";
            echo "   👥 Connexions: " . ($data['connections'] ?? 'N/A') . "\n";
        } else {
            echo "   ❌ Health check échoué (HTTP $httpCode)\n";
        }
    }

    private function diagnosticComplete() {
        echo "\n5. Diagnostic Complet...\n";

        echo "   🔍 Vérifications à effectuer :\n";
        echo "   - [ ] Labels supprimés dans Coolify\n";
        echo "   - [ ] Nouveaux labels Caddy ajoutés\n";
        echo "   - [ ] Force deploy effectué\n";
        echo "   - [ ] Service redémarré\n";
        echo "   - [ ] Configuration Caddy appliquée\n";

        echo "\n   🛠️  Commandes de debug :\n";
        echo "   docker inspect sue-websocket | grep -A 20 \"Labels\"\n";
        echo "   docker exec caddy cat /etc/caddy/Caddyfile\n";
        echo "   docker logs caddy --tail 50\n";
        echo "   docker logs sue-websocket --tail 50\n";

        echo "\n   📋 Prochaines étapes :\n";
        echo "   1. Vérifier les labels dans Coolify\n";
        echo "   2. Supprimer tous les labels existants\n";
        echo "   3. Ajouter la configuration Caddy\n";
        echo "   4. Force deploy sans cache\n";
        echo "   5. Tester la connectivité\n";
    }
}

// Exécuter le debug
$debug = new CoolifyLabelsDebug();
$debug->run();
