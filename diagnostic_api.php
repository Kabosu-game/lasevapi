<?php
/**
 * Script de diagnostic API - Capture l'origine exacte des erreurs
 * 
 * Usage:
 *   php diagnostic_api.php              Test API en ligne (nécessite curl/SSL)
 *   php diagnostic_api.php -k           Ignorer la vérification SSL (si erreur certificat)
 *   php diagnostic_api_internal.php     Test INTERNE (sans HTTP, depuis Laravel)
 * 
 * Le script internal est recommandé : il teste directement le contrôleur
 * et affiche l'origine exacte de chaque erreur (422, 500, 503).
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$baseUrl = 'https://lasevapi.o-sterebois.fr/api';

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  DIAGNOSTIC API - lasevapi.o-sterebois.fr                   ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$results = [];

// Test 1: create-stripe-payment-intent
echo "[1] POST /api/create-stripe-payment-intent\n";
echo str_repeat("-", 60) . "\n";

$payload = [
    'amount' => 2500,
    'currency' => 'EUR',
    'email' => 'test@example.com',
];

$ch = curl_init($baseUrl . '/create-stripe-payment-intent');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_HEADER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_VERBOSE => false,
    CURLOPT_SSL_VERIFYPEER => !in_array('-k', $argv ?? []),
]);

$response = curl_exec($ch);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);

$responseHeaders = substr($response, 0, $headerSize);
$responseBody = substr($response, $headerSize);

if ($curlErrno) {
    echo "❌ ERREUR CURL: [$curlErrno] $curlError\n";
    echo "   → Problème de connexion réseau ou SSL\n";
} else {
    echo "   HTTP Status: $httpCode\n";
    echo "   Headers:\n";
    foreach (explode("\n", trim($responseHeaders)) as $h) {
        if (!empty(trim($h))) echo "      $h\n";
    }
    echo "   Body (raw): " . var_export($responseBody, true) . "\n";
    
    $json = json_decode($responseBody, true);
    if ($json) {
        echo "\n   Body (JSON parsé):\n";
        echo "   " . json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        
        if ($httpCode >= 400) {
            echo "\n   ┌── ORIGINE DE L'ERREUR ──────────────────────────────┐\n";
            if (isset($json['error'])) {
                echo "   │ Erreur API: " . $json['error'] . "\n";
            }
            if (isset($json['errors']) && is_array($json['errors'])) {
                echo "   │ Validation (champ => message):\n";
                foreach ($json['errors'] as $field => $msgs) {
                    $msg = is_array($msgs) ? implode(', ', $msgs) : $msgs;
                    echo "   │   - $field: $msg\n";
                }
            }
            if (isset($json['message'])) {
                echo "   │ Message: " . $json['message'] . "\n";
            }
            if ($httpCode === 422) {
                echo "   │\n";
                echo "   │ Cause probable: données envoyées invalides\n";
                echo "   │ Données envoyées: " . json_encode($payload) . "\n";
            }
            if ($httpCode === 500) {
                echo "   │\n";
                echo "   │ Cause: erreur serveur (voir logs Laravel)\n";
            }
            if ($httpCode === 503) {
                echo "   │\n";
                echo "   │ Cause: Stripe non configuré (STRIPE_SECRET_KEY manquant)\n";
            }
            echo "   └──────────────────────────────────────────────────────┘\n";
        }
    } else {
        echo "\n   ⚠ Body n'est pas du JSON valide\n";
        if (!empty($responseBody)) {
            echo "   Contenu brut: " . substr($responseBody, 0, 500) . "\n";
        }
    }
}

echo "\n";

// Test 2: Vérifier que l'API répond
echo "[2] GET /api/retreat-plans (contrôle)\n";
echo str_repeat("-", 60) . "\n";

$ch2 = curl_init($baseUrl . '/retreat-plans');
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
]);
$resp2 = curl_exec($ch2);
$code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

echo "   HTTP Status: $code2\n";
if ($code2 === 200) {
    $data = json_decode($resp2, true);
    echo "   ✓ API accessible (" . (is_array($data) ? count($data) : 0) . " plans)\n";
} else {
    echo "   ✗ API inaccessibile ou erreur\n";
}

echo "\n";
echo "══════════════════════════════════════════════════════════════\n";
echo "Fin du diagnostic - " . date('Y-m-d H:i:s') . "\n";
echo "══════════════════════════════════════════════════════════════\n\n";
