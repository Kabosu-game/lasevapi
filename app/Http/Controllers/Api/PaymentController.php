<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Models\RetreatPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;

class PaymentController extends Controller
{
    // -------------------------------------------------------------------------
    // HELPERS PRIVÉS
    // -------------------------------------------------------------------------

    /**
     * Retourne un access token OAuth2 PayPal (Client Credentials).
     * FIX: PayPal v2 n'accepte PAS Basic Auth sur les endpoints /orders.
     *      Il faut d'abord obtenir un Bearer token, puis l'utiliser.
     */
    private function getPayPalAccessToken(): string
    {
        $clientId = config('payments.paypal.client_id');
        $secret   = config('payments.paypal.secret');
        $baseUrl  = config('payments.paypal.mode', 'sandbox') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        if (empty($clientId) || empty($secret)) {
            throw new \RuntimeException('PayPal client_id ou secret non configuré dans payments.php.');
        }

        $ch = curl_init("{$baseUrl}/v1/oauth2/token");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => "{$clientId}:{$secret}",
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            throw new \RuntimeException("Impossible d'obtenir le token PayPal (HTTP {$httpCode}).");
        }

        $data = json_decode($response, true);

        if (empty($data['access_token'])) {
            throw new \RuntimeException('Réponse PayPal invalide : ' . json_encode($data));
        }

        return $data['access_token'];
    }

    /**
     * Initialise la clé Stripe et lève une exception claire si elle est absente.
     * FIX: sans cette vérification, Stripe lance "unexpected value" sans contexte.
     */
    private function initStripe(): void
    {
        $key = config('payments.stripe.secret_key');

        if (empty($key)) {
            throw new \RuntimeException('La clé secrète Stripe (payments.stripe.secret_key) est manquante ou vide.');
        }

        Stripe::setApiKey($key);
    }

    /**
     * Retourne la currency Stripe en minuscules et valide qu'elle n'est pas nulle.
     * FIX: strtolower(null) retourne "" → Stripe lève "unexpected value" sur currency.
     */
    private function getStripeCurrency(): string
    {
        $currency = config('payments.stripe.currency');

        if (empty($currency)) {
            throw new \RuntimeException('La devise Stripe (payments.stripe.currency) n\'est pas configurée.');
        }

        return strtolower($currency);
    }

    // -------------------------------------------------------------------------
    // ENDPOINTS
    // -------------------------------------------------------------------------

    /**
     * Paiement d'un retreat plan + création ou connexion utilisateur (simulation).
     */
    public function payAndRegisterOrLogin(Request $request, $planId)
    {
        $validator = Validator::make($request->all(), [
            'device_id'      => 'required|string',
            'email'          => 'required|email',
            'password'       => 'required|string|min:6',
            'amount'         => 'required|numeric|min:0.01',
            'currency'       => 'required|string|size:3',
            'payment_method' => 'nullable|string',
            'transaction_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // FIX: chercher device_id et email séparément pour éviter de croiser deux users différents
        $userByDevice = User::where('device_id', $request->device_id)->first();
        $userByEmail  = User::where('email', $request->email)->first();

        if ($userByDevice && $userByEmail && $userByDevice->id !== $userByEmail->id) {
            // Conflit : device_id appartient à un user, email à un autre
            return response()->json([
                'success' => false,
                'message' => 'Conflit d\'identité : device_id et email correspondent à deux comptes différents.',
            ], 409);
        }

        $user = $userByDevice ?? $userByEmail;

        if ($user) {
            $updated = false;

            if (empty($user->email)) {
                $user->email = $request->email;
                $updated = true;
            }

            if (empty($user->password)) {
                $user->password = Hash::make($request->password);
                $updated = true;
            }

            if ($updated) {
                $user->save();
            } elseif (!Hash::check($request->password, $user->password)) {
                return response()->json(['success' => false, 'message' => 'Mot de passe incorrect.'], 401);
            }
        } else {
            $user = User::create([
                'name'      => $request->email,
                'email'     => $request->email,
                'device_id' => $request->device_id,
                'password'  => Hash::make($request->password),
            ]);
        }

        $payment = Payment::create([
            'retreat_plan_id' => $planId,
            'user_id'         => $user->id,
            'amount'          => $request->amount,
            'currency'        => strtoupper($request->currency),
            'status'          => 'completed',
            'payment_method'  => $request->payment_method,
            'transaction_id'  => $request->transaction_id,
            'paid_at'         => now(),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'user'    => $user,
            'payment' => $payment,
            'token'   => $token,
        ], 201);
    }

    /**
     * Créer une intention de paiement Stripe.
     */
    public function createStripePaymentIntent(Request $request)
    {
        try {
            $request->validate([
                'retreat_plan_id' => 'required|exists:retreat_plans,id',
                'payment_method'  => 'required|in:stripe',
            ]);

            $retreatPlan = RetreatPlan::findOrFail($request->retreat_plan_id);
            $user        = Auth::user();

            if (empty($retreatPlan->price) || $retreatPlan->price <= 0) {
                return response()->json([
                    'message' => 'Ce plan n\'a pas de prix valide.',
                    'status'  => 'error',
                ], 400);
            }

            // FIX: validation de la config avant tout appel Stripe
            $this->initStripe();
            $currency = $this->getStripeCurrency();

            // FIX: s'assurer que l'amount est bien un entier > 0
            $amountInCents = (int) round($retreatPlan->price * 100);

            if ($amountInCents <= 0) {
                return response()->json([
                    'message' => 'Le montant calculé est invalide.',
                    'status'  => 'error',
                ], 400);
            }

            $paymentIntent = PaymentIntent::create([
                'amount'               => $amountInCents,
                'currency'             => $currency,
                'payment_method_types' => ['card'],
                'metadata'             => [
                    'retreat_plan_id' => $retreatPlan->id,
                    'user_id'         => $user->id,
                    'user_email'      => $user->email,
                ],
                'description' => "Paiement - {$retreatPlan->title}",
            ]);

            $payment = Payment::create([
                'retreat_plan_id' => $retreatPlan->id,
                'user_id'         => $user->id,
                'amount'          => $retreatPlan->price,
                'currency'        => strtoupper($currency),
                'status'          => 'pending',
                'payment_method'  => 'stripe',
                'transaction_id'  => $paymentIntent->id,
            ]);

            return response()->json([
                'client_secret'     => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'amount'            => $retreatPlan->price,
                'retreat_plan'      => $retreatPlan,
                'status'            => 'success',
            ]);
        } catch (ApiErrorException $e) {
            // FIX: catch séparé pour les erreurs Stripe → message précis dans les logs
            Log::error('Erreur Stripe API: ' . $e->getMessage(), [
                'stripe_code' => $e->getStripeCode(),
                'http_status' => $e->getHttpStatus(),
            ]);
            return response()->json([
                'message' => 'Erreur Stripe : ' . $e->getMessage(),
                'status'  => 'error',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Erreur createStripePaymentIntent: ' . $e->getMessage());
            return response()->json([
                'message' => $e->getMessage(),
                'status'  => 'error',
            ], 500);
        }
    }

    /**
     * Confirmer le paiement Stripe.
     */
    public function confirmStripePayment(Request $request)
    {
        try {
            $request->validate([
                'payment_intent_id' => 'required|string',
                'retreat_plan_id'   => 'required|exists:retreat_plans,id',
            ]);

            $user    = Auth::user();
            $payment = Payment::where('transaction_id', $request->payment_intent_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$payment) {
                return response()->json([
                    'message' => 'Paiement non trouvé.',
                    'status'  => 'error',
                ], 404);
            }

            $this->initStripe();
            $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);

            if ($paymentIntent->status === 'succeeded') {
                $payment->update([
                    'status'  => 'completed',
                    'paid_at' => now(),
                ]);

                return response()->json([
                    'message' => 'Paiement confirmé avec succès.',
                    'payment' => $payment,
                    'status'  => 'success',
                ]);
            }

            return response()->json([
                'message' => 'Le paiement n\'a pas encore été confirmé.',
                'status'  => $paymentIntent->status,
            ], 400);
        } catch (ApiErrorException $e) {
            Log::error('Erreur Stripe confirm: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erreur Stripe : ' . $e->getMessage(),
                'status'  => 'error',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Erreur confirmStripePayment: ' . $e->getMessage());
            return response()->json([
                'message' => $e->getMessage(),
                'status'  => 'error',
            ], 500);
        }
    }

    /**
     * Créer une commande PayPal.
     * FIX MAJEUR: utilisation d'un Bearer token OAuth2 au lieu de Basic Auth.
     */
    public function createPayPalOrder(Request $request)
    {
        try {
            $request->validate([
                'retreat_plan_id' => 'required|exists:retreat_plans,id',
                'payment_method'  => 'required|in:paypal',
            ]);

            $retreatPlan = RetreatPlan::findOrFail($request->retreat_plan_id);
            $user        = Auth::user();

            if (empty($retreatPlan->price) || $retreatPlan->price <= 0) {
                return response()->json([
                    'message' => 'Ce plan n\'a pas de prix valide.',
                    'status'  => 'error',
                ], 400);
            }

            $currency = config('payments.paypal.currency', 'USD');
            $baseUrl  = config('payments.paypal.mode', 'sandbox') === 'live'
                ? 'https://api-m.paypal.com'
                : 'https://api-m.sandbox.paypal.com';

            // FIX: on obtient d'abord le token OAuth2
            $accessToken = $this->getPayPalAccessToken();

            $body = json_encode([
                'intent'         => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => 'retreat_plan_' . $retreatPlan->id,
                    'amount'       => [
                        'currency_code' => $currency,
                        'value'         => number_format($retreatPlan->price, 2, '.', ''),
                    ],
                    'description'  => $retreatPlan->title,
                ]],
                'payer' => [
                    'email_address' => $user->email,
                    'name'          => [
                        'given_name' => $user->first_name ?? 'User',
                        'surname'    => $user->last_name  ?? '',
                    ],
                ],
            ]);

            $ch = curl_init("{$baseUrl}/v2/checkout/orders");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    // FIX: Bearer token, pas Basic
                    'Authorization: Bearer ' . $accessToken,
                ],
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $orderData = json_decode($response, true);

            if ($httpCode === 201 && isset($orderData['id'])) {
                $payment = Payment::create([
                    'retreat_plan_id' => $retreatPlan->id,
                    'user_id'         => $user->id,
                    'amount'          => $retreatPlan->price,
                    'currency'        => $currency,
                    'status'          => 'pending',
                    'payment_method'  => 'paypal',
                    'transaction_id'  => $orderData['id'],
                ]);

                return response()->json([
                    'order_id'    => $orderData['id'],
                    'payment'     => $payment,
                    'amount'      => $retreatPlan->price,
                    'retreat_plan' => $retreatPlan,
                    'status'      => 'success',
                ]);
            }

            Log::error('PayPal createOrder erreur', ['http_code' => $httpCode, 'response' => $orderData]);

            return response()->json([
                'message' => 'Erreur lors de la création de la commande PayPal.',
                'error'   => $orderData,
                'status'  => 'error',
            ], 400);
        } catch (\Exception $e) {
            Log::error('Erreur createPayPalOrder: ' . $e->getMessage());
            return response()->json([
                'message' => $e->getMessage(),
                'status'  => 'error',
            ], 500);
        }
    }

    /**
     * Capturer la commande PayPal.
     * FIX MAJEUR: même correction OAuth2 Bearer token.
     */
    public function capturePayPalOrder(Request $request)
    {
        try {
            $request->validate([
                'order_id'        => 'required|string',
                'retreat_plan_id' => 'required|exists:retreat_plans,id',
            ]);

            $user    = Auth::user();
            $payment = Payment::where('transaction_id', $request->order_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$payment) {
                return response()->json([
                    'message' => 'Paiement non trouvé.',
                    'status'  => 'error',
                ], 404);
            }

            $baseUrl     = config('payments.paypal.mode', 'sandbox') === 'live'
                ? 'https://api-m.paypal.com'
                : 'https://api-m.sandbox.paypal.com';
            $accessToken = $this->getPayPalAccessToken();

            $ch = curl_init("{$baseUrl}/v2/checkout/orders/{$request->order_id}/capture");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => '{}',
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $accessToken,
                ],
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $orderData = json_decode($response, true);

            if ($httpCode === 201 && isset($orderData['status']) && $orderData['status'] === 'COMPLETED') {
                $payment->update([
                    'status'  => 'completed',
                    'paid_at' => now(),
                ]);

                return response()->json([
                    'message' => 'Paiement confirmé avec succès.',
                    'payment' => $payment,
                    'status'  => 'success',
                ]);
            }

            Log::error('PayPal capture erreur', ['http_code' => $httpCode, 'response' => $orderData]);

            return response()->json([
                'message' => 'Le paiement n\'a pas pu être capturé.',
                'error'   => $orderData,
                'status'  => 'error',
            ], 400);
        } catch (\Exception $e) {
            Log::error('Erreur capturePayPalOrder: ' . $e->getMessage());
            return response()->json([
                'message' => $e->getMessage(),
                'status'  => 'error',
            ], 500);
        }
    }

    /**
     * Historique des paiements de l'utilisateur connecté.
     */
    public function getPaymentHistory()
    {
        try {
            $user     = Auth::user();
            $payments = Payment::where('user_id', $user->id)
                ->with('retreatPlan')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'payments' => $payments,
                'status'   => 'success',
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur getPaymentHistory: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erreur lors de la récupération de l\'historique.',
                'error'   => $e->getMessage(),
                'status'  => 'error',
            ], 500);
        }
    }

    /**
     * Récupérer un paiement spécifique.
     */
    public function getPayment($paymentId)
    {
        try {
            $user    = Auth::user();
            $payment = Payment::where('id', $paymentId)
                ->where('user_id', $user->id)
                ->with('retreatPlan')
                ->firstOrFail();

            return response()->json([
                'payment' => $payment,
                'status'  => 'success',
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur getPayment: ' . $e->getMessage());
            return response()->json([
                'message' => 'Paiement non trouvé.',
                'error'   => $e->getMessage(),
                'status'  => 'error',
            ], 404);
        }
    }
}
