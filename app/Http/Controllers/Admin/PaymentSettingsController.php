<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class PaymentSettingsController extends Controller
{
    // -------------------------------------------------------------------------
    // PAGES
    // -------------------------------------------------------------------------

    public function index()
    {
        try {
            [$stripe, $paypal, $paypalMode] = $this->resolveConfig();

            return view('admin.payment-settings.index', [
                'paypalMode'             => $paypalMode,
                'stripeSecretKeyMasked'  => $this->maskKey($stripe['secret_key'] ?? ''),
                'stripePublicKeyMasked'  => $this->maskKey($stripe['public_key'] ?? ''),
                'paypalClientIdMasked'   => $this->maskKey($paypal['client_id'] ?? ''),
                'paypalSecretMasked'     => $this->maskKey($paypal['secret'] ?? ''),
            ]);
        } catch (\Throwable $e) {
            Log::error('PaymentSettings index error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            // Affiche un message clair au lieu d'un 500 blanc
            return response()->view('errors.500', [
                'message' => 'Erreur de configuration paiements : ' . $e->getMessage(),
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // MISE À JOUR .env
    // -------------------------------------------------------------------------

  public function update(Request $request)
{
    try {
        $request->validate([
            'stripe_secret_key' => 'nullable|string|max:255',
            'stripe_public_key' => 'nullable|string|max:255',
            'paypal_client_id'  => 'nullable|string|max:255',
            'paypal_secret'     => 'nullable|string|max:255',
            'paypal_mode'       => 'required|in:live,sandbox',
        ]);

        $envPath = base_path('.env');

        if (!file_exists($envPath) || !is_readable($envPath)) {
            return back()->with('error', '.env introuvable ou non lisible.');
        }

        $envContent = file_get_contents($envPath);

        $map = [
            'stripe_secret_key' => 'STRIPE_SECRET_KEY',
            'stripe_public_key' => 'STRIPE_PUBLIC_KEY',
            'paypal_client_id'  => 'PAYPAL_CLIENT_ID',
            'paypal_secret'     => 'PAYPAL_SECRET',
        ];

        foreach ($map as $inputKey => $envKey) {
            if ($request->filled($inputKey)) {
                $envContent = $this->updateEnvKey($envContent, $envKey, $request->input($inputKey));
            }
        }

        $envContent = $this->updateEnvKey($envContent, 'PAYPAL_MODE', $request->paypal_mode);

        $result = file_put_contents($envPath, $envContent);
        if ($result === false) {
            return back()->with('error', 'Impossible d\'écrire dans .env — vérifiez les permissions.');
        }

        Artisan::call('config:clear');

        return redirect()->route('admin.payment-settings.index')
            ->with('success', 'Configuration mise à jour.');

    } catch (\Throwable $e) {
        Log::error('PaymentSettings update error: ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
        return back()->with('error', 'Erreur : ' . $e->getMessage());
    }
}

    // -------------------------------------------------------------------------
    // TESTS API
    // -------------------------------------------------------------------------

    public function testStripe()
    {
        try {
            $stripeKey = config('payments.stripe.secret_key');

            if (empty($stripeKey)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Clé Stripe secrète manquante dans la config.',
                ], 400);
            }

            \Stripe\Stripe::setApiKey($stripeKey);
            $account = \Stripe\Account::retrieve();

            return response()->json([
                'status'     => 'success',
                'message'    => 'Connexion Stripe réussie',
                'account_id' => $account->id,
                'email'      => $account->email    ?? 'N/A',
                'country'    => $account->country  ?? 'N/A',
            ]);
        } catch (\Stripe\Exception\AuthenticationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Clé Stripe invalide : ' . $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Stripe test error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Erreur Stripe : ' . $e->getMessage(),
            ], 500);
        }
    }

    public function testPayPal()
    {
        try {
            $clientId = config('payments.paypal.client_id');
            $secret   = config('payments.paypal.secret');
            $mode     = config('payments.paypal.mode', 'sandbox');

            if (empty($clientId) || empty($secret)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Identifiants PayPal manquants dans la config.',
                ], 400);
            }

            // FIX: test réel OAuth2 au lieu d'une vérification de string arbitraire
            $baseUrl = $mode === 'live'
                ? 'https://api-m.paypal.com'
                : 'https://api-m.sandbox.paypal.com';

            $ch = curl_init("{$baseUrl}/v1/oauth2/token");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERPWD        => "{$clientId}:{$secret}",
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
                CURLOPT_HTTPHEADER     => ['Accept: application/json'],
                CURLOPT_TIMEOUT        => 10,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode($response, true);

            if ($httpCode === 200 && !empty($data['access_token'])) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Connexion PayPal réussie',
                    'mode'    => $mode,
                ]);
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Identifiants PayPal invalides (HTTP ' . $httpCode . ')',
                'detail'  => $data['error_description'] ?? 'Aucun détail',
            ], 400);
        } catch (\Exception $e) {
            Log::error('PayPal test error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Erreur PayPal : ' . $e->getMessage(),
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // DIAGNOSTIC
    // -------------------------------------------------------------------------

    public function diagnostic()
    {
        $steps = [];

        try {
            $configPayments = config('payments');

            if (!is_array($configPayments)) {
                return response()->json([
                    'ok'    => false,
                    'error' => 'config("payments") non trouvée',
                    'hint'  => 'Vérifiez que config/payments.php existe sur le serveur',
                ]);
            }
            $steps[] = 'payments_config ok';

            [$stripe, $paypal, $paypalMode] = $this->resolveConfig();
            $steps[] = 'resolve_config ok';

            $viewPath = resource_path('views/admin/payment-settings/index.blade.php');
            if (!file_exists($viewPath)) {
                return response()->json([
                    'ok'    => false,
                    'error' => 'Vue non trouvée : ' . $viewPath,
                    'hint'  => 'Vérifiez le déploiement (sensibilité à la casse sur Linux)',
                ]);
            }
            $steps[] = 'view_file ok';

            view('admin.payment-settings.index', [
                'paypalMode'            => $paypalMode,
                'stripeSecretKeyMasked' => $this->maskKey($stripe['secret_key'] ?? ''),
                'stripePublicKeyMasked' => $this->maskKey($stripe['public_key'] ?? ''),
                'paypalClientIdMasked'  => $this->maskKey($paypal['client_id'] ?? ''),
                'paypalSecretMasked'    => $this->maskKey($paypal['secret'] ?? ''),
            ])->render();
            $steps[] = 'view_render ok';

            return response()->json([
                'ok'             => true,
                'message'        => 'Tous les tests passent',
                'steps'          => $steps,
                'php_version'    => PHP_VERSION,
                'config_cached'  => app()->configurationIsCached(),
            ]);
        } catch (\Throwable $e) {
            Log::error('PaymentSettings diagnostic error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'ok'    => false,
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'steps' => $steps,
                'class' => get_class($e),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // HELPERS PRIVÉS
    // -------------------------------------------------------------------------

    /**
     * Résout et valide la config paiements.
     * @return array [stripe[], paypal[], paypalMode]
     */
    private function resolveConfig(): array
    {
        $configPayments = config('payments');

        if (!is_array($configPayments)) {
            throw new \RuntimeException(
                'config("payments") manquante. Lancez: php artisan config:clear'
            );
        }

        $stripe    = $configPayments['stripe'] ?? [];
        $paypal    = $configPayments['paypal'] ?? [];
        $paypalMode = $paypal['mode'] ?? 'sandbox';

        if (!is_string($paypalMode) || !in_array($paypalMode, ['live', 'sandbox'], true)) {
            $paypalMode = 'sandbox';
        }

        return [$stripe, $paypal, $paypalMode];
    }

    /**
     * Met à jour ou ajoute une clé dans le contenu .env.
     */
    private function updateEnvKey(string $envContent, string $key, string $value): string
    {
        // FIX: échappe les caractères spéciaux dans la valeur
        $escapedValue = str_contains($value, ' ') ? '"' . $value . '"' : $value;
        $pattern      = "/^{$key}=.*/m";
        $replacement  = "{$key}={$escapedValue}";

        if (preg_match($pattern, $envContent)) {
            return preg_replace($pattern, $replacement, $envContent);
        }

        return rtrim($envContent) . "\n{$key}={$escapedValue}\n";
    }

    /**
     * Masque une clé pour l'affichage sécurisé.
     */
    private function maskKey(string $key): string
    {
        if ($key === '') {
            return '';
        }

        $length  = strlen($key);
        $visible = min(6, max(3, (int) floor($length / 5)));

        return substr($key, 0, $visible) . str_repeat('*', max(0, $length - $visible));
    }
}
