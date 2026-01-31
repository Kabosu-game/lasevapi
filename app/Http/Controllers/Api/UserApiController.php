<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserApiController extends Controller
{
    // Login
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Email ou mot de passe incorrect'], 401);
        }

        // Créer un token API
        $token = $user->createToken('app-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ], 200);
    }

    // Récupérer les paiements de l'utilisateur
    public function getPayments(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        // Récupérer les paiements de l'utilisateur
        $payments = Payment::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                    'created_at' => $payment->created_at,
                    'retreat_plan_name' => $payment->retreatPlan?->title ?? 'Retraite',
                    'payment_method' => $payment->payment_method,
                    'transaction_id' => $payment->transaction_id,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    // Récupérer les statistiques de paiement
    public function getPaymentStats(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        // Récupérer les paiements de l'utilisateur
        $payments = Payment::where('user_id', $user->id)->get();

        $totalSpent = $payments->where('status', 'completed')->sum('amount');
        $totalPayments = $payments->count();
        $completedPayments = $payments->where('status', 'completed')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_spent' => $totalSpent,
                'total_payments' => $totalPayments,
                'completed_payments' => $completedPayments,
                'next_payment_date' => null,
            ],
        ]);
    }

    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnecté avec succès']);
    }
}
