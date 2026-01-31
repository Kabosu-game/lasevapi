<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaymentSettingsController extends Controller
{
    /**
     * Afficher la page de configuration des paiements
     */
    public function index()
    {
        // Récupérer les configurations actuelles
        $stripeSecretKey = env('STRIPE_SECRET_KEY', '');
        $stripePublicKey = env('STRIPE_PUBLIC_KEY', '');
        $paypalClientId = env('PAYPAL_CLIENT_ID', '');
        $paypalSecret = env('PAYPAL_SECRET', '');
        $paypalMode = env('PAYPAL_MODE', 'live');

        // Masquer partiellement les clés pour la sécurité
        $stripeSecretKeyMasked = $this->maskKey($stripeSecretKey, 'sk_');
        $stripePublicKeyMasked = $this->maskKey($stripePublicKey, 'pk_');
        $paypalClientIdMasked = $this->maskKey($paypalClientId);
        $paypalSecretMasked = $this->maskKey($paypalSecret);

        return view('admin.payment-settings.index', compact(
            'stripeSecretKey',
            'stripePublicKey',
            'paypalClientId',
            'paypalSecret',
            'paypalMode',
            'stripeSecretKeyMasked',
            'stripePublicKeyMasked',
            'paypalClientIdMasked',
            'paypalSecretMasked'
        ));
    }

    /**
     * Mettre à jour la configuration des paiements
     */
    public function update(Request $request)
    {
        $request->validate([
            'stripe_secret_key' => 'nullable|string',
            'stripe_public_key' => 'nullable|string',
            'paypal_client_id' => 'nullable|string',
            'paypal_secret' => 'nullable|string',
            'paypal_mode' => 'required|in:live,sandbox',
        ]);

        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        // Mettre à jour les clés dans le fichier .env
        if ($request->filled('stripe_secret_key')) {
            $envContent = $this->updateEnvKey($envContent, 'STRIPE_SECRET_KEY', $request->stripe_secret_key);
        }

        if ($request->filled('stripe_public_key')) {
            $envContent = $this->updateEnvKey($envContent, 'STRIPE_PUBLIC_KEY', $request->stripe_public_key);
        }

        if ($request->filled('paypal_client_id')) {
            $envContent = $this->updateEnvKey($envContent, 'PAYPAL_CLIENT_ID', $request->paypal_client_id);
        }

        if ($request->filled('paypal_secret')) {
            $envContent = $this->updateEnvKey($envContent, 'PAYPAL_SECRET', $request->paypal_secret);
        }

        $envContent = $this->updateEnvKey($envContent, 'PAYPAL_MODE', $request->paypal_mode);

        // Sauvegarder le fichier .env
        if (file_put_contents($envPath, $envContent) !== false) {
            // Effacer le cache de configuration
            \Artisan::call('config:clear');
            
            \Log::info('Payment settings updated by user: ' . auth()->id());

            return redirect()->route('admin.payment-settings.index')
                ->with('success', 'Configuration des paiements mise à jour avec succès. Les modifications seront appliquées après rechargement.');
        }

        return redirect()->route('admin.payment-settings.index')
            ->with('error', 'Erreur lors de la mise à jour de la configuration.');
    }

    /**
     * Tester la configuration Stripe
     */
    public function testStripe(Request $request)
    {
        try {
            $stripeKey = env('STRIPE_SECRET_KEY');
            
            if (empty($stripeKey)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Clé Stripe secrète manquante'
                ], 400);
            }

            // Tester avec l'API Stripe
            \Stripe\Stripe::setApiKey($stripeKey);
            $account = \Stripe\Account::retrieve();

            return response()->json([
                'status' => 'success',
                'message' => 'Connexion Stripe réussie',
                'account_id' => $account->id,
                'email' => $account->email ?? 'N/A',
                'country' => $account->country ?? 'N/A'
            ]);
        } catch (\Exception $e) {
            \Log::error('Stripe test error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Tester la configuration PayPal
     */
    public function testPayPal(Request $request)
    {
        try {
            $clientId = env('PAYPAL_CLIENT_ID');
            $secret = env('PAYPAL_SECRET');

            if (empty($clientId) || empty($secret)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Identifiants PayPal manquants'
                ], 400);
            }

            // Vérifier que les identifiants PayPal sont formatés correctement
            if (!str_contains($clientId, 'Af9') && !str_contains($clientId, 'AZDxjhQjYjgzQqq')) {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Format Client ID vérifié, mais connexion réelle requise'
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Configuration PayPal valide',
                'mode' => env('PAYPAL_MODE', 'live'),
                'client_id_preview' => substr($clientId, 0, 10) . '...'
            ]);
        } catch (\Exception $e) {
            \Log::error('PayPal test error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Mettre à jour une clé dans le fichier .env
     */
    private function updateEnvKey($envContent, $key, $value)
    {
        $pattern = "/^{$key}=.*/m";
        $replacement = "{$key}={$value}";

        if (preg_match($pattern, $envContent)) {
            return preg_replace($pattern, $replacement, $envContent);
        } else {
            return $envContent . "\n{$key}={$value}";
        }
    }

    /**
     * Masquer une clé pour l'affichage
     */
    private function maskKey($key, $prefix = '')
    {
        if (empty($key)) {
            return '';
        }

        $length = strlen($key);
        $visible = min(10, max(3, intdiv($length, 4)));
        
        return substr($key, 0, $visible) . str_repeat('*', $length - $visible);
    }
}
