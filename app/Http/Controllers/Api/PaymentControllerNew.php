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

class PaymentController extends Controller
{
    public function __construct()
    {
        // Initialiser Stripe avec la clé secrète
        Stripe::setApiKey(config('payments.stripe.secret_key'));
    }

    /**
     * Paiement d'un retreat plan + création ou connexion utilisateur
     * POST /api/retreat-plans/{planId}/pay
     */
    public function payAndRegisterOrLogin(Request $request, $planId)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'amount' => 'required|numeric',
            'currency' => 'required|string',
            'payment_method' => 'nullable|string',
            'transaction_id' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = User::where('device_id', $request->device_id)->orWhere('email', $request->email)->first();
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
                'name' => $request->email,
                'email' => $request->email,
                'device_id' => $request->device_id,
                'password' => Hash::make($request->password),
            ]);
        }

        // Créer l'enregistrement de paiement
        $payment = Payment::create([
            'retreat_plan_id' => $planId,
            'user_id' => $user->id,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'status' => 'completed',
            'payment_method' => $request->payment_method,
            'transaction_id' => $request->transaction_id,
            'paid_at' => now(),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;
        return response()->json([
            'success' => true,
            'user' => $user,
            'payment' => $payment,
            'token' => $token
        ], 201);
    }

    // ==================== STRIPE ====================

    /**
     * Crée une intention de paiement Stripe
     * POST /api/create-stripe-payment-intent
     */
    public function createStripePaymentIntent(Request $request)
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:100',
                'currency' => 'required|string|size:3',
                'email' => 'required|email',
            ]);

            // Créer l'intention de paiement Stripe
            $paymentIntent = PaymentIntent::create([
                'amount' => (int)$validated['amount'],
                'currency' => strtolower($validated['currency']),
                'payment_method_types' => ['card'],
                'receipt_email' => $validated['email'],
                'metadata' => [
                    'order_id' => uniqid(),
                    'timestamp' => now()->timestamp,
                ],
            ]);

            Log::info('Stripe PaymentIntent créé', [
                'id' => $paymentIntent->id,
                'amount' => $validated['amount'],
                'email' => $validated['email'],
            ]);

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'publishableKey' => config('payments.stripe.public_key'),
                'id' => $paymentIntent->id,
                'amount' => $validated['amount'],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur Stripe PaymentIntent', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Capture un paiement Stripe
     * POST /api/capture-stripe-payment
     */
    public function captureStripePayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'paymentIntentId' => 'required|string',
            ]);

            // Récupérer l'intention de paiement
            $paymentIntent = PaymentIntent::retrieve($validated['paymentIntentId']);

            // Vérifier que le paiement a réussi
            if ($paymentIntent->status === 'succeeded') {
                Log::info('Paiement Stripe capturé', [
                    'id' => $paymentIntent->id,
                    'status' => $paymentIntent->status,
                    'amount' => $paymentIntent->amount,
                ]);

                return response()->json([
                    'id' => $paymentIntent->id,
                    'status' => $paymentIntent->status,
                    'amount' => $paymentIntent->amount,
                    'currency' => $paymentIntent->currency,
                ], 200);
            } else {
                return response()->json([
                    'error' => 'Paiement non confirmé',
                    'status' => $paymentIntent->status,
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Erreur capture Stripe', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    // ==================== PAYPAL ====================

    /**
     * Crée une commande PayPal
     * POST /api/create-paypal-order
     */
    public function createPayPalOrder(Request $request)
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:1',
                'currency' => 'required|string|size:3',
                'description' => 'required|string',
            ]);

            // Utiliser le SDK PayPal si disponible, sinon faire un appel cURL
            $paypalMode = config('payments.paypal.mode', 'sandbox');
            $apiUrl = $paypalMode === 'sandbox'
                ? 'https://api-m.sandbox.paypal.com'
                : 'https://api-m.paypal.com';

            // Obtenir le token d'authentification
            $authToken = $this->getPayPalAuthToken();
            if (!$authToken) {
                return response()->json(['error' => 'Erreur PayPal auth'], 400);
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
                            'reference_id' => uniqid(),
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
                        'return_url' => route('payment.paypal-return'),
                        'cancel_url' => route('payment.paypal-cancel'),
                    ],
                ],
            ]);

            $body = json_decode($response->getBody(), true);

            Log::info('Commande PayPal créée', ['id' => $body['id']]);

            return response()->json($body, 200);
        } catch (\Exception $e) {
            Log::error('Erreur PayPal order', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Approuve et capture une commande PayPal
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

            // Obtenir le token d'authentification
            $authToken = $this->getPayPalAuthToken();
            if (!$authToken) {
                return response()->json(['error' => 'Erreur PayPal auth'], 400);
            }

            // Capturer le paiement
            $client = new \GuzzleHttp\Client();
            $response = $client->post(
                $apiUrl . '/v2/checkout/orders/' . $validated['orderId'] . '/capture',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $authToken,
                        'Content-Type' => 'application/json',
                    ],
                ]
            );

            $body = json_decode($response->getBody(), true);

            Log::info('Paiement PayPal capturé', [
                'orderId' => $validated['orderId'],
                'status' => $body['status'],
            ]);

            return response()->json($body, 200);
        } catch (\Exception $e) {
            Log::error('Erreur PayPal capture', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    // ==================== ENREGISTREMENT ====================

    /**
     * Enregistre un paiement dans la base de données
     * POST /api/record-payment
     */
    public function recordPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'transactionId' => 'required|string',
                'method' => 'required|in:stripe,paypal',
                'amount' => 'required|numeric|min:0',
                'status' => 'required|string',
                'userId' => 'nullable|string',
                'retreatPlanId' => 'nullable|string',
            ]);

            // Créer l'enregistrement de paiement
            $payment = Payment::create([
                'transaction_id' => $validated['transactionId'],
                'method' => $validated['method'],
                'amount' => $validated['amount'],
                'status' => $validated['status'],
                'user_id' => $validated['userId'],
                'retreat_plan_id' => $validated['retreatPlanId'],
                'paid_at' => now(),
            ]);

            Log::info('Paiement enregistré', [
                'id' => $payment->id,
                'method' => $validated['method'],
                'amount' => $validated['amount'],
            ]);

            return response()->json([
                'id' => $payment->id,
                'status' => 'recorded',
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur enregistrement paiement', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Récupère l'historique de paiement d'un utilisateur
     * GET /api/user/payments
     */
    public function getUserPayments(Request $request)
    {
        try {
            $userId = auth()->id();
            $payments = Payment::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'data' => $payments->items(),
                'total' => $payments->total(),
                'per_page' => $payments->perPage(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Récupère les statistiques de paiement d'un utilisateur
     * GET /api/user/payment-stats
     */
    public function getPaymentStats(Request $request)
    {
        try {
            $userId = auth()->id();
            $payments = Payment::where('user_id', $userId)
                ->where('status', 'completed')
                ->get();

            return response()->json([
                'data' => [
                    'total_amount' => $payments->sum('amount'),
                    'total_count' => $payments->count(),
                    'by_method' => $payments->groupBy('method')
                        ->map(fn($group) => [
                            'count' => $group->count(),
                            'amount' => $group->sum('amount'),
                        ])
                        ->toArray(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    // ==================== HELPERS ====================

    /**
     * Obtient un token d'authentification PayPal
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

            $client = new \GuzzleHttp\Client();
            $response = $client->post($apiUrl . '/v1/oauth2/token', [
                'auth' => [$clientId, $secret],
                'form_params' => [
                    'grant_type' => 'client_credentials',
                ],
            ]);

            $body = json_decode($response->getBody(), true);
            return $body['access_token'] ?? null;
        } catch (\Exception $e) {
            Log::error('Erreur PayPal auth token', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Endpoint de retour PayPal
     */
    public function paypalReturn(Request $request)
    {
        $orderId = $request->query('token');
        return view('payment.paypal-return', ['orderId' => $orderId]);
    }

    /**
     * Endpoint d'annulation PayPal
     */
    public function paypalCancel(Request $request)
    {
        return view('payment.paypal-cancel');
    }
}
