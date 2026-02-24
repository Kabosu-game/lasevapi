<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PublicPaymentController extends Controller
{
    /**
     * Créer une intention de paiement Stripe (PUBLIC - sans SDK)
     * POST /api/create-stripe-payment-intent
     */
    public function createStripePaymentIntent(Request $request)
    {
        try {
            // Normaliser les données (email vide -> générer, amount en centimes)
            $input = $request->all();
            if (empty(trim((string) ($input['email'] ?? '')))) {
                $input['email'] = 'client' . time() . '@lasev.com';
            }
            $request->merge($input);

            $validated = $request->validate([
                'amount' => 'required|numeric|min:50',
                'currency' => 'required|string|size:3',
                'email' => 'required|email',
                'description' => 'nullable|string',
            ], [
                'amount.required' => 'Le montant est requis.',
                'amount.numeric' => 'Le montant doit être un nombre.',
                'amount.min' => 'Le montant minimum est 50 centimes.',
                'email.required' => 'L\'email est requis.',
                'email.email' => 'L\'email doit être valide.',
            ]);

            $email = $validated['email'];

            // Créer une PaymentIntent via l'API Stripe
            $stripeSecretKey = config('payments.stripe.secret_key');
            $stripePublicKey = config('payments.stripe.public_key');
            if (!$stripeSecretKey || !$stripePublicKey) {
                Log::warning('Stripe non configuré sur le serveur. Définir STRIPE_SECRET_KEY et STRIPE_PUBLIC_KEY dans .env');
                return response()->json([
                    'success' => false,
                    'error' => 'Paiement non configuré. Contactez l\'administrateur (STRIPE_SECRET_KEY manquant sur le serveur).',
                ], 503);
            }

            // Appel HTTP à Stripe API - Flutter envoie déjà le montant en centimes
            $client = new \GuzzleHttp\Client();
            $verifySsl = env('STRIPE_VERIFY_SSL', env('APP_ENV') === 'production');
            $response = $client->post('https://api.stripe.com/v1/payment_intents', [
                'auth' => [$stripeSecretKey, ''],
                'form_params' => [
                    'amount' => (int) $validated['amount'],
                    'currency' => strtolower($validated['currency']),
                    'receipt_email' => $email,
                    'description' => $validated['description'] ?? 'LASEV Retreat Payment',
                    'metadata' => ['email' => $email],
                ],
                'verify' => filter_var($verifySsl, FILTER_VALIDATE_BOOLEAN),
            ]);

            $body = json_decode($response->getBody(), true);

            Log::info('Stripe PaymentIntent created', ['id' => $body['id']]);

            return response()->json([
                'success' => true,
                'clientSecret' => $body['client_secret'],
                'publishableKey' => config('payments.stripe.public_key'),
                'intentId' => $body['id'],
            ]);
        } catch (ValidationException $e) {
            $msg = implode(' ', $e->validator->errors()->all());
            Log::warning('Stripe PaymentIntent validation échouée', [
                'errors' => $e->validator->errors()->toArray(),
                'received' => $request->only(['amount', 'currency', 'email']),
            ]);
            return response()->json([
                'success' => false,
                'error' => $msg,
                'errors' => $e->validator->errors()->toArray(),
            ], 422);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $body = $e->hasResponse() ? (string) $e->getResponse()->getBody() : '';
            Log::error('Stripe API erreur', [
                'http_status' => $status,
                'body' => substr($body, 0, 500),
                'message' => $e->getMessage(),
            ]);
            $hint = $status === 401 ? 'Clé Stripe invalide (vérifier STRIPE_SECRET_KEY)' : null;
            $hint ??= $status === 402 ? 'Paiement refusé par Stripe' : null;
            $hint ??= (str_contains($body, 'No such payment_intent') ? 'PaymentIntent introuvable' : null);
            return response()->json([
                'success' => false,
                'error' => $hint ?? 'Erreur Stripe ('.$status.'). Vérifier configuration.',
                'code' => 'STRIPE_ERROR',
            ], 502);
        } catch (\Throwable $e) {
            Log::error('Erreur création Stripe PaymentIntent', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            $msg = config('app.debug') ? $e->getMessage() : 'Erreur paiement: ' . get_class($e);
            return response()->json([
                'success' => false,
                'error' => $msg,
                'code' => 'PAYMENT_ERROR',
            ], 500);
        }
    }

    /**
     * Capturer un paiement Stripe (PUBLIC)
     * POST /api/capture-stripe-payment
     */
    public function captureStripePayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'paymentIntentId' => 'required|string',
            ]);

            // Récupérer l'intention de paiement depuis Stripe
            $stripeSecretKey = config('payments.stripe.secret_key');
            $client = new \GuzzleHttp\Client();
            
            $response = $client->get(
                'https://api.stripe.com/v1/payment_intents/' . $validated['paymentIntentId'],
                [
                    'auth' => [$stripeSecretKey, ''],
                    'verify' => env('APP_ENV') === 'production',
                ]
            );

            $intent = json_decode($response->getBody(), true);

            if ($intent['status'] === 'succeeded') {
                return response()->json([
                    'success' => true,
                    'transactionId' => $intent['id'],
                    'status' => 'completed',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Payment not succeeded. Status: ' . $intent['status'],
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Erreur capture Stripe', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Créer une commande PayPal (PUBLIC)
     * POST /api/create-paypal-order
     */
    public function createPayPalOrder(Request $request)
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'currency' => 'required|string|size:3',
                'description' => 'required|string',
                'email' => 'nullable|email',
            ]);

            $paypalMode = config('payments.paypal.mode', 'sandbox');
            $apiUrl = $paypalMode === 'sandbox'
                ? 'https://api-m.sandbox.paypal.com'
                : 'https://api-m.paypal.com';

            // Obtenir le token d'authentification PayPal
            $authToken = $this->getPayPalAuthToken();
            if (!$authToken) {
                Log::error('Impossible d\'obtenir le token PayPal');
                return response()->json(['success' => false, 'error' => 'PayPal auth failed'], 500);
            }

            // Créer la commande
            $client = new \GuzzleHttp\Client();
            $response = $client->post($apiUrl . '/v2/checkout/orders', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $authToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [
                        [
                            'reference_id' => uniqid('order_'),
                            'amount' => [
                                'currency_code' => strtoupper($validated['currency']),
                                'value' => (string)$validated['amount'],
                            ],
                            'description' => $validated['description'],
                        ],
                    ],
                    'application_context' => [
                        'brand_name' => 'LASEV',
                        'user_action' => 'PAY_NOW',
                        'return_url' => 'http://localhost:3000',
                        'cancel_url' => 'http://localhost:3000',
                    ],
                ],
                'verify' => env('APP_ENV') === 'production',
            ]);

            $body = json_decode($response->getBody(), true);

            Log::info('PayPal order created', ['id' => $body['id']]);

            return response()->json([
                'success' => true,
                'orderId' => $body['id'],
                'approvalUrl' => collect($body['links'])->firstWhere('rel', 'approve')['href'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur création PayPal order', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approuver une commande PayPal (PUBLIC)
     * POST /api/approve-paypal-order
     */
    public function approvePayPalOrder(Request $request)
    {
        try {
            $validated = $request->validate([
                'orderId' => 'required|string',
                'payerId' => 'required|string',
            ]);

            $paypalMode = config('payments.paypal.mode', 'sandbox');
            $apiUrl = $paypalMode === 'sandbox'
                ? 'https://api-m.sandbox.paypal.com'
                : 'https://api-m.paypal.com';

            $authToken = $this->getPayPalAuthToken();
            if (!$authToken) {
                return response()->json(['success' => false, 'error' => 'PayPal auth failed'], 500);
            }

            // Capturer la commande
            $client = new \GuzzleHttp\Client();
            $response = $client->post(
                $apiUrl . '/v2/checkout/orders/' . $validated['orderId'] . '/capture',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $authToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => ['payer_id' => $validated['payerId']],
                    'verify' => env('APP_ENV') === 'production',
                ]
            );

            $body = json_decode($response->getBody(), true);

            if ($body['status'] === 'COMPLETED') {
                Log::info('PayPal order approved', ['id' => $validated['orderId']]);
                return response()->json([
                    'success' => true,
                    'transactionId' => $body['id'],
                    'status' => 'completed',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Order not completed',
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Erreur approbation PayPal', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enregistrer un paiement (PUBLIC)
     * POST /api/record-payment
     */
    public function recordPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'transactionId' => 'required|string',
                'method' => 'required|in:stripe,paypal',
                'amount' => 'required|numeric',
                'status' => 'required|in:pending,completed,failed',
                'userId' => 'required|string',
                'retreatPlanId' => 'required|exists:retreat_plans,id',
                'email' => 'nullable|email',
            ]);

            $payment = Payment::create([
                'transaction_id' => $validated['transactionId'],
                'payment_method' => $validated['method'],
                'amount' => $validated['amount'],
                'status' => $validated['status'],
                'currency' => 'EUR',
                'user_id' => (int) $validated['userId'],
                'retreat_plan_id' => $validated['retreatPlanId'] ?? null,
                'paid_at' => $validated['status'] === 'completed' ? now() : null,
            ]);

            Log::info('Payment recorded', ['id' => $payment->id]);

            return response()->json([
                'success' => true,
                'paymentId' => $payment->id,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur enregistrement paiement', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir le token d'authentification PayPal
     */
    private function getPayPalAuthToken()
    {
        try {
            $paypalMode = config('payments.paypal.mode', 'sandbox');
            $apiUrl = $paypalMode === 'sandbox'
                ? 'https://api-m.sandbox.paypal.com'
                : 'https://api-m.paypal.com';

            $clientId = config('payments.paypal.client_id');
            $secret = config('payments.paypal.secret');

            if (!$clientId || !$secret) {
                Log::error('PayPal credentials missing', [
                    'clientId' => $clientId ? 'set' : 'missing',
                    'secret' => $secret ? 'set' : 'missing',
                ]);
                return null;
            }

            $client = new \GuzzleHttp\Client();
            $response = $client->post($apiUrl . '/v1/oauth2/token', [
                'auth' => [$clientId, $secret],
                'form_params' => ['grant_type' => 'client_credentials'],
                'verify' => env('APP_ENV') === 'production', // Désactiver vérif SSL en dev
            ]);

            $body = json_decode($response->getBody(), true);
            return $body['access_token'] ?? null;
        } catch (\Exception $e) {
            Log::error('Erreur obtention token PayPal', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
