<?php

$baseUrl = 'http://localhost:8000/api';
$token = '2|6MaOzmTqrSukpIVW2QCerM7j3lxY0WUICx6sJ83148099557';

echo "🔍 Test de création de session\n";
echo "==============================\n\n";

// Test 1: Vérifier l'utilisateur connecté
echo "1. Vérification de l'utilisateur connecté...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/users/profile');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code HTTP: $httpCode\n";
echo "Réponse: " . $response . "\n\n";

// Test 2: Créer une session
echo "2. Création d'une session...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/sessions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'sport' => 'tennis',
    'date' => date('Y-m-d', strtotime('+1 week')),
    'time' => '18:00',
    'location' => 'Tennis Club de Paris'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code HTTP: $httpCode\n";
echo "Réponse: " . $response . "\n\n";

// Test 3: Lister les sessions
echo "3. Liste des sessions...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/sessions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code HTTP: $httpCode\n";
echo "Réponse: " . $response . "\n\n";
