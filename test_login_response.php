<?php

/**
 * Test pour vérifier que l'endpoint /api/login retourne firstname et lastname
 * au lieu de full_name
 */

class LoginResponseTest
{
    private string $baseUrl = 'http://localhost:8000/api';
    private string $testEmail = 'test@example.com';
    private string $testPassword = 'password123';

    public function runTest(): void
    {
        echo "🧪 Test de la réponse de connexion\n";
        echo "==================================\n\n";

        // 1. D'abord, créer un utilisateur de test
        echo "1. Création d'un utilisateur de test...\n";
        $this->createTestUser();

        // 2. Tester la connexion
        echo "2. Test de connexion...\n";
        $this->testLogin();

        echo "\n✅ Test terminé\n";
    }

    private function createTestUser(): void
    {
        $data = [
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => $this->testEmail,
            'password' => $this->testPassword,
            'device_name' => 'Test Device'
        ];

        $response = $this->makeRequest('POST', '/register', $data);

        if (isset($response['user'])) {
            echo "   ✅ Utilisateur créé avec succès\n";
            echo "   - ID: " . $response['user']['id'] . "\n";
            echo "   - Email: " . $response['user']['email'] . "\n";
            echo "   - Firstname: " . $response['user']['firstname'] . "\n";
            echo "   - Lastname: " . $response['user']['lastname'] . "\n";
        } else {
            echo "   ❌ Erreur lors de la création de l'utilisateur\n";
            print_r($response);
        }
    }

    private function testLogin(): void
    {
        $data = [
            'email' => $this->testEmail,
            'password' => $this->testPassword,
            'device_name' => 'Test Device'
        ];

        $response = $this->makeRequest('POST', '/login', $data);

        if (isset($response['user'])) {
            echo "   ✅ Connexion réussie\n";
            echo "   - Message: " . $response['message'] . "\n";
            echo "   - Token: " . substr($response['token'], 0, 20) . "...\n";
            echo "   - User ID: " . $response['user']['id'] . "\n";
            echo "   - Email: " . $response['user']['email'] . "\n";

            // Vérifier la présence de firstname et lastname
            if (isset($response['user']['firstname']) && isset($response['user']['lastname'])) {
                echo "   ✅ Firstname et lastname présents\n";
                echo "   - Firstname: " . $response['user']['firstname'] . "\n";
                echo "   - Lastname: " . $response['user']['lastname'] . "\n";
            } else {
                echo "   ❌ Firstname et lastname manquants\n";
            }

            // Vérifier l'absence de full_name
            if (!isset($response['user']['full_name'])) {
                echo "   ✅ Full_name absent (correct)\n";
            } else {
                echo "   ❌ Full_name présent (incorrect): " . $response['user']['full_name'] . "\n";
            }

            // Vérifier la présence de role
            if (isset($response['user']['role'])) {
                echo "   ✅ Role présent: " . $response['user']['role'] . "\n";
            } else {
                echo "   ❌ Role manquant\n";
            }

        } else {
            echo "   ❌ Erreur lors de la connexion\n";
            print_r($response);
        }
    }

    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decodedResponse = json_decode($response, true);

        if ($decodedResponse === null) {
            return ['error' => 'Invalid JSON response', 'raw' => $response];
        }

        return $decodedResponse;
    }
}

// Exécuter le test
$test = new LoginResponseTest();
$test->runTest();
