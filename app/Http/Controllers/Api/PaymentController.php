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
    /**
     * Paiement d'un retreat plan + création ou connexion utilisateur
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
            // Si l'utilisateur n'a pas d'email ou de mot de passe, on complète son compte
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
            // Création du compte
            $user = User::create([
                'name' => $request->email,
                'email' => $request->email,
                'device_id' => $request->device_id,
                'password' => Hash::make($request->password),
            ]);
        }

        // Paiement (simulation)
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

    /**
     * Créer une intention de paiement Stripe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createStripePaymentIntent(Request $request)
    {
        try {
            $request->validate([
                'retreat_plan_id' => 'required|exists:retreat_plans,id',
                'payment_method' => 'required|in:stripe',
            ]);

            $retreatPlan = RetreatPlan::findOrFail($request->retreat_plan_id);
            $user = Auth::user();

            if (!$retreatPlan->price) {
                return response()->json([
                    'message' => 'Ce plan n\'a pas de prix défini',
                    'status' => 'error'
                ], 400);
            }

            // Initialiser Stripe
            Stripe::setApiKey(config('payments.stripe.secret_key'));

            // Créer une intention de paiement
            $paymentIntent = PaymentIntent::create([
                'amount' => (int)($retreatPlan->price * 100), // Stripe utilise les centimes
                'currency' => strtolower(config('payments.stripe.currency')),
                'payment_method_types' => ['card'],
                'metadata' => [
                    'retreat_plan_id' => $retreatPlan->id,
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                ],
                'description' => "Paiement - {$retreatPlan->title}",
            ]);

            // Créer un enregistrement de paiement en attente
            $payment = Payment::create([
                'retreat_plan_id' => $retreatPlan->id,
                'user_id' => $user->id,
                'amount' => $retreatPlan->price,
                'currency' => config('payments.stripe.currency'),
                'status' => 'pending',
                'payment_method' => 'stripe',
                'transaction_id' => $paymentIntent->id,
            ]);

            return response()->json([
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $retreatPlan->price,
                'retreat_plan' => $retreatPlan,
                'status' => 'success'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur Stripe: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erreur lors de la création du paiement',
                'error' => $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Confirmer le paiement Stripe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmStripePayment(Request $request)
    {
        try {
            $request->validate([
                'payment_intent_id' => 'required|string',
                'retreat_plan_id' => 'required|exists:retreat_plans,id',
            ]);

            $user = Auth::user();
            $payment = Payment::where('transaction_id', $request->payment_intent_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$payment) {
                return response()->json([
                    'message' => 'Paiement non trouvé',
                    'status' => 'error'
                ], 404);
            }

            // Vérifier l'état du paiement auprès de Stripe
            Stripe::setApiKey(config('payments.stripe.secret_key'));
            $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);

            if ($paymentIntent->status === 'succeeded') {
                $payment->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                ]);

                return response()->json([
                    'message' => 'Paiement confirmé avec succès',
                    'payment' => $payment,
                    'status' => 'success'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Le paiement n\'a pas été confirmé',
                    'status' => $paymentIntent->status,
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Erreur confirmation Stripe: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erreur lors de la confirmation du paiement',
                'error' => $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Créer une commande PayPal
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPayPalOrder(Request $request)
    {
        try {
            $request->validate([
                'retreat_plan_id' => 'required|exists:retreat_plans,id',
                'payment_method' => 'required|in:paypal',
            ]);

            $retreatPlan = RetreatPlan::findOrFail($request->retreat_plan_id);
            $user = Auth::user();

            if (!$retreatPlan->price) {
                return response()->json([
                    'message' => 'Ce plan n\'a pas de prix défini',
                    'status' => 'error'
                ], 400);
            }

            // Créer une requête d'ordre PayPal
            $ch = curl_init();
            
            $auth = base64_encode(
                config('payments.paypal.client_id') . ':' . config('payments.paypal.secret')
            );

            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://api.paypal.com/v2/checkout/orders',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Basic ' . $auth,
                ],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'intent' => 'CAPTURE',
                    'purchase_units' => [
                        [
                            'reference_id' => 'retreat_plan_' . $retreatPlan->id,
                            'amount' => [
                                'currency_code' => config('payments.paypal.currency'),
                                'value' => strval($retreatPlan->price),
                            ],
                            'description' => $retreatPlan->title,
                        ]
                    ],
                    'payer' => [
                        'email_address' => $user->email,
                        'name' => [
                            'given_name' => $user->first_name ?? 'User',
                            'surname' => $user->last_name ?? '',
                        ],
                    ],
                ]),
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            $orderData = json_decode($response, true);

            if (isset($orderData['id'])) {
                // Créer un enregistrement de paiement en attente
                $payment = Payment::create([
                    'retreat_plan_id' => $retreatPlan->id,
                    'user_id' => $user->id,
                    'amount' => $retreatPlan->price,
                    'currency' => config('payments.paypal.currency'),
                    'status' => 'pending',
                    'payment_method' => 'paypal',
                    'transaction_id' => $orderData['id'],
                ]);

                return response()->json([
                    'order_id' => $orderData['id'],
                    'payment' => $payment,
                    'amount' => $retreatPlan->price,
                    'retreat_plan' => $retreatPlan,
                    'status' => 'success'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Erreur lors de la création de la commande PayPal',
                    'error' => $orderData,
                    'status' => 'error'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Erreur PayPal: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erreur lors de la création de la commande',
                'error' => $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Capturer la commande PayPal
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function capturePayPalOrder(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|string',
                'retreat_plan_id' => 'required|exists:retreat_plans,id',
            ]);

            $user = Auth::user();
            $payment = Payment::where('transaction_id', $request->order_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$payment) {
                return response()->json([
                    'message' => 'Paiement non trouvé',
                    'status' => 'error'
                ], 404);
            }

            // Capturer l'ordre PayPal
            $ch = curl_init();
            
            $auth = base64_encode(
                config('payments.paypal.client_id') . ':' . config('payments.paypal.secret')
            );

            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://api.paypal.com/v2/checkout/orders/' . $request->order_id . '/capture',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Basic ' . $auth,
                ],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => '{}',
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            $orderData = json_decode($response, true);

            if (isset($orderData['status']) && $orderData['status'] === 'COMPLETED') {
                $payment->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                ]);

                return response()->json([
                    'message' => 'Paiement confirmé avec succès',
                    'payment' => $payment,
                    'status' => 'success'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Le paiement n\'a pas pu être capturé',
                    'error' => $orderData,
                    'status' => 'error'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Erreur capture PayPal: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erreur lors de la capture du paiement',
                'error' => $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Récupérer l'historique des paiements de l'utilisateur
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentHistory()
    {
        try {
            $user = Auth::user();
            $payments = Payment::where('user_id', $user->id)
                ->with('retreatPlan')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'payments' => $payments,
                'status' => 'success'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur historique paiement: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erreur lors de la récupération de l\'historique',
                'error' => $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Récupérer un paiement spécifique
     * @param $paymentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPayment($paymentId)
    {
        try {
            $user = Auth::user();
            $payment = Payment::where('id', $paymentId)
                ->where('user_id', $user->id)
                ->with('retreatPlan')
                ->firstOrFail();

            return response()->json([
                'payment' => $payment,
                'status' => 'success'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur récupération paiement: ' . $e->getMessage());
            return response()->json([
                'message' => 'Paiement non trouvé',
                'error' => $e->getMessage(),
                'status' => 'error'
            ], 404);
        }
    }
}

