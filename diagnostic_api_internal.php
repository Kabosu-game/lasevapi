<?php
/**
 * Script de diagnostic INTERNE - Teste l'API depuis Laravel (sans HTTP)
 * Capture l'erreur exacte côté serveur
 * Usage: php diagnostic_api_internal.php
 */
$apiDir = __DIR__;
require $apiDir . '/vendor/autoload.php';

$app = require_once $apiDir . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Api\PublicPaymentController;

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  DIAGNOSTIC INTERNE - Test direct du contrôleur             ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$controller = new PublicPaymentController();

// Cas de test
$testCases = [
    ['amount' => 2500, 'currency' => 'EUR', 'email' => 'test@example.com'],
    ['amount' => 2500, 'currency' => 'EUR', 'email' => ''],
    ['amount' => 0, 'currency' => 'EUR', 'email' => 'test@example.com'],
    ['amount' => 2500, 'currency' => 'XX', 'email' => 'test@example.com'],
    [], // body vide
];

foreach ($testCases as $i => $payload) {
    $num = $i + 1;
    echo "[$num] Test avec: " . json_encode($payload) . "\n";
    echo str_repeat("-", 60) . "\n";
    
    $request = Request::create(
        '/api/create-stripe-payment-intent',
        'POST',
        $payload,
        [],
        [],
        ['CONTENT_TYPE' => 'application/json'],
        json_encode($payload)
    );
    $request->headers->set('Accept', 'application/json');
    
    try {
        $response = $controller->createStripePaymentIntent($request);
        $status = $response->getStatusCode();
        $body = $response->getContent();
        $json = json_decode($body, true);
        
        echo "   HTTP: $status\n";
        echo "   Body: " . json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        
        if ($status >= 400) {
            echo "\n   ┌── ORIGINE ERREUR ──────────────────────────────────┐\n";
            if (isset($json['error'])) echo "   │ error: " . $json['error'] . "\n";
            if (isset($json['errors'])) {
                foreach ($json['errors'] as $k => $v) {
                    echo "   │ $k: " . (is_array($v) ? implode(', ', $v) : $v) . "\n";
                }
            }
            echo "   └─────────────────────────────────────────────────────┘\n";
        }
    } catch (\Throwable $e) {
        echo "   ❌ EXCEPTION: " . $e->getMessage() . "\n";
        echo "   Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "   Trace:\n" . $e->getTraceAsString() . "\n";
    }
    
    echo "\n";
}

// Vérifier config Stripe
echo "[Config] Stripe\n";
echo str_repeat("-", 60) . "\n";
$secret = config('payments.stripe.secret_key');
$public = config('payments.stripe.public_key');
echo "   STRIPE_SECRET_KEY: " . ($secret ? '✓ défini (' . substr($secret, 0, 12) . '...)' : '✗ MANQUANT') . "\n";
echo "   STRIPE_PUBLIC_KEY: " . ($public ? '✓ défini (' . substr($public, 0, 12) . '...)' : '✗ MANQUANT') . "\n";
echo "\n";
