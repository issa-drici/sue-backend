<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ExpoPushNotificationService
{
    private const EXPO_API_URL = 'https://exp.host/--/api/v2/push/send';
    private const MAX_TOKENS_PER_REQUEST = 100; // Limite d'Expo par requête

    /**
     * Envoyer une notification push à un ou plusieurs tokens
     */
    public function sendNotification(array $tokens, string $title, string $body, array $data = []): array
    {
        if (empty($tokens)) {
            return [
                'success' => false,
                'error' => 'Aucun token fourni'
            ];
        }

        // Diviser les tokens en lots pour respecter la limite d'Expo
        $tokenChunks = array_chunk($tokens, self::MAX_TOKENS_PER_REQUEST);
        $results = [];

        foreach ($tokenChunks as $chunk) {
            $messages = [];

            foreach ($chunk as $token) {
                $messages[] = [
                    'to' => $token,
                    'sound' => 'default',
                    'title' => $title,
                    'body' => $body,
                    'data' => $data,
                    'priority' => 'high',
                    'channelId' => 'default',
                ];
            }

            $result = $this->sendToExpo($messages);
            $results[] = $result;

            // Log des réponses de tickets Expo
            if (!empty($result['response'])) {
                Log::info('Expo response', [
                    'response' => $result['response']
                ]);
            }
        }

        return $this->aggregateResults($results);
    }

    /**
     * Envoyer une notification à un seul token
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): array
    {
        return $this->sendNotification([$token], $title, $body, $data);
    }

    /**
     * Envoyer une notification à un utilisateur (tous ses tokens)
     */
    public function sendToUser(string $userId, string $title, string $body, array $data = []): array
    {
        // Cette méthode sera utilisée avec le repository des tokens
        // Pour l'instant, on retourne une structure vide
        return [
            'success' => false,
            'error' => 'Méthode à implémenter avec le repository'
        ];
    }

    /**
     * Envoyer les messages à l'API Expo
     */
    private function sendToExpo(array $messages): array
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::EXPO_API_URL);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($messages));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Accept-encoding: gzip, deflate',
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                Log::error('Expo Push Notification cURL Error', [
                    'error' => $error,
                    'messages_count' => count($messages)
                ]);

                return [
                    'success' => false,
                    'error' => 'cURL Error: ' . $error,
                    'http_code' => 0
                ];
            }

            $responseData = json_decode($response, true);

            if ($httpCode !== 200) {
                Log::error('Expo Push Notification API Error', [
                    'http_code' => $httpCode,
                    'response' => $responseData,
                    'messages_count' => count($messages)
                ]);

                return [
                    'success' => false,
                    'error' => 'API Error: HTTP ' . $httpCode,
                    'http_code' => $httpCode,
                    'response' => $responseData
                ];
            }

            // Analyser les résultats d'Expo
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            $invalidTokens = [];

            // L'API Expo peut retourner soit directement un tableau, soit dans un champ 'data'
            $results = $responseData;
            if (isset($responseData['data']) && is_array($responseData['data'])) {
                $results = $responseData['data'];
            }

            if (is_array($results)) {
                foreach ($results as $index => $result) {
                    if (isset($result['status'])) {
                        if ($result['status'] === 'ok') {
                            $successCount++;
                        } else {
                            $errorCount++;
                            if (isset($result['message'])) {
                                $errors[] = $result['message'];
                            }
                            // Détection de tokens invalides selon Expo
                            $detailsError = $result['details']['error'] ?? null;
                            $shouldInvalidate = in_array($detailsError, ['DeviceNotRegistered', 'InvalidCredentials', 'MessageTooBig', 'MessageRateExceeded', 'InvalidPushToken']);
                            if ($shouldInvalidate && isset($messages[$index]['to'])) {
                                $invalidTokens[] = $messages[$index]['to'];
                            }
                        }
                    } else {
                        // Si pas de statut mais qu'on a un ID, c'est probablement un succès
                        if (isset($result['id'])) {
                            $successCount++;
                        } else {
                            $errorCount++;
                            $errors[] = 'Statut manquant dans la réponse';
                        }
                    }
                }
            }

            $result = [
                'success' => $successCount > 0,
                'http_code' => $httpCode,
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'total_sent' => count($messages),
                'response' => $responseData,
                'invalid_tokens' => $invalidTokens
            ];

            if (!empty($errors)) {
                $result['errors'] = $errors;
            }

            // Log des résultats
            if ($errorCount > 0) {
                Log::warning('Expo Push Notification Partial Success', [
                    'success_count' => $successCount,
                    'error_count' => $errorCount,
                    'errors' => $errors
                ]);
            } else {
                Log::info('Expo Push Notification Success', [
                    'success_count' => $successCount,
                    'total_sent' => count($messages)
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Expo Push Notification Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'messages_count' => count($messages)
            ]);

            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage(),
                'http_code' => 0
            ];
        }
    }

    /**
     * Agréger les résultats de plusieurs requêtes
     */
    private function aggregateResults(array $results): array
    {
        $totalSuccess = 0;
        $totalError = 0;
        $totalSent = 0;
        $allErrors = [];
        $hasSuccess = false;

        foreach ($results as $result) {
            if ($result['success']) {
                $hasSuccess = true;
                $totalSuccess += $result['success_count'] ?? 0;
            }
            $totalError += $result['error_count'] ?? 0;
            $totalSent += $result['total_sent'] ?? 0;

            if (isset($result['errors'])) {
                $allErrors = array_merge($allErrors, $result['errors']);
            }
        }

        // Si l'API Expo a répondu correctement (HTTP 200), on considère que c'est un succès
        // même si certains tokens sont invalides
        $apiResponded = false;
        foreach ($results as $result) {
            if (isset($result['http_code']) && $result['http_code'] === 200) {
                $apiResponded = true;
                break;
            }
        }

        return [
            'success' => $apiResponded, // L'API a répondu correctement
            'total_success_count' => $totalSuccess,
            'total_error_count' => $totalError,
            'total_sent' => $totalSent,
            'errors' => $allErrors,
            'results' => $results
        ];
    }

    /**
     * Valider un token Expo
     */
    public function isValidToken(string $token): bool
    {
        // Format de base d'un token Expo
        return preg_match('/^ExponentPushToken\[.+\]$/', $token) === 1;
    }

    /**
     * Nettoyer les tokens invalides
     */
    public function filterValidTokens(array $tokens): array
    {
        return array_filter($tokens, [$this, 'isValidToken']);
    }
}
