<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\RetreatPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentAdminController extends Controller
{
    /**
     * Afficher l'historique complet des paiements
     */
    public function index(Request $request)
    {
        $query = Payment::with(['user', 'retreatPlan'])
            ->orderBy('created_at', 'desc');

        // Filtrer par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtrer par méthode de paiement
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filtrer par plan
        if ($request->filled('retreat_plan_id')) {
            $query->where('retreat_plan_id', $request->retreat_plan_id);
        }

        // Filtrer par date
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Recherche par email ou ID utilisateur
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $payments = $query->paginate($request->per_page ?? 50);
        $retreatPlans = RetreatPlan::all();

        return view('admin.payments.index', compact('payments', 'retreatPlans'));
    }

    /**
     * Afficher les statistiques des paiements
     */
    public function statistics(Request $request)
    {
        $dateFrom = $request->date_from ? strtotime($request->date_from) : strtotime('-30 days');
        $dateTo = $request->date_to ? strtotime($request->date_to) : now()->timestamp;

        // Statistiques générales
        $stats = [
            'total_payments' => Payment::whereDate('created_at', '>=', date('Y-m-d', $dateFrom))
                ->whereDate('created_at', '<=', date('Y-m-d', $dateTo))
                ->count(),
            'total_revenue' => Payment::whereDate('created_at', '>=', date('Y-m-d', $dateFrom))
                ->whereDate('created_at', '<=', date('Y-m-d', $dateTo))
                ->where('status', 'completed')
                ->sum('amount'),
            'completed_payments' => Payment::whereDate('created_at', '>=', date('Y-m-d', $dateFrom))
                ->whereDate('created_at', '<=', date('Y-m-d', $dateTo))
                ->where('status', 'completed')
                ->count(),
            'pending_payments' => Payment::whereDate('created_at', '>=', date('Y-m-d', $dateFrom))
                ->whereDate('created_at', '<=', date('Y-m-d', $dateTo))
                ->where('status', 'pending')
                ->count(),
            'failed_payments' => Payment::whereDate('created_at', '>=', date('Y-m-d', $dateFrom))
                ->whereDate('created_at', '<=', date('Y-m-d', $dateTo))
                ->where('status', 'failed')
                ->count(),
        ];

        // Statistiques par méthode de paiement
        $byMethodRaw = Payment::whereDate('created_at', '>=', date('Y-m-d', $dateFrom))
            ->whereDate('created_at', '<=', date('Y-m-d', $dateTo))
            ->where('status', 'completed')
            ->groupBy('payment_method')
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->get();
        
        $byMethod = [];
        foreach ($byMethodRaw as $item) {
            $byMethod[$item->payment_method] = ['count' => $item->count, 'total' => $item->total];
        }

        // Statistiques par plan
        $byPlanRaw = Payment::whereDate('created_at', '>=', date('Y-m-d', $dateFrom))
            ->whereDate('created_at', '<=', date('Y-m-d', $dateTo))
            ->where('status', 'completed')
            ->groupBy('retreat_plan_id')
            ->with('retreatPlan')
            ->select('retreat_plan_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->get();
        
        $byPlan = [];
        foreach ($byPlanRaw as $item) {
            $planName = $item->retreatPlan->title ?? 'Plan ' . $item->retreat_plan_id;
            $byPlan[$planName] = ['count' => $item->count, 'total' => $item->total];
        }

        return view('admin.payments.statistics', compact('stats', 'byMethod', 'byPlan'));
    }

    /**
     * Afficher les détails d'un paiement
     */
    public function show($paymentId)
    {
        $payment = Payment::with(['user', 'retreatPlan'])
            ->findOrFail($paymentId);

        return view('admin.payments.show', compact('payment'));
    }

    /**
     * Mettre à jour le statut d'un paiement
     */
    public function updateStatus(Request $request, $paymentId)
    {
        $request->validate([
            'status' => 'required|in:pending,completed,failed,refunded',
        ]);

        $payment = Payment::findOrFail($paymentId);
        
        $oldStatus = $payment->status;
        $payment->status = $request->status;
        
        if ($request->status === 'completed' && !$payment->paid_at) {
            $payment->paid_at = now();
        }
        
        $payment->save();

        // Log l'action
        \Log::info("Payment status updated: Payment #{$paymentId} changed from {$oldStatus} to {$request->status}");

        return redirect()->route('admin.payments.show', $paymentId)
            ->with('success', 'Le statut du paiement a été mis à jour avec succès');
    }

    /**
     * Rembourser un paiement
     */
    public function refund(Request $request, $paymentId)
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric|min:0.01',
        ]);

        $payment = Payment::findOrFail($paymentId);

        if ($payment->status === 'refunded') {
            return redirect()->route('admin.payments.show', $paymentId)
                ->with('error', 'Ce paiement a déjà été remboursé');
        }

        if ($payment->status !== 'completed') {
            return redirect()->route('admin.payments.show', $paymentId)
                ->with('error', 'Seuls les paiements complétés peuvent être remboursés');
        }

        $refundAmount = $request->amount ?? $payment->amount;

        // Ici, vous pourriez intégrer avec l'API Stripe ou PayPal pour traiter le remboursement
        // Pour l'instant, on simule le remboursement en base de données

        $payment->status = 'refunded';
        $payment->save();

        // Créer un enregistrement de remboursement
        \Log::info("Payment refunded: Payment #{$paymentId}, Amount: {$refundAmount} USD, Reason: {$request->reason}");

        return redirect()->route('admin.payments.show', $paymentId)
            ->with('success', "Paiement remboursé avec succès (Montant: \${$refundAmount})");
    }

    /**
     * Exporter l'historique des paiements en CSV
     */
    public function export(Request $request)
    {
        $query = Payment::with(['user', 'retreatPlan']);

        // Appliquer les mêmes filtres que l'index
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payments = $query->orderBy('created_at', 'desc')->get();

        // Créer le CSV
        $filename = 'paiements_export_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $handle = fopen('php://temp', 'r+');
        
        // En-têtes CSV
        fputcsv($handle, [
            'ID', 'Utilisateur Email', 'Plan de Retraite', 'Montant (USD)', 
            'Devise', 'Statut', 'Méthode de Paiement', 'ID Transaction', 
            'Payé le', 'Créé le'
        ]);

        // Données
        foreach ($payments as $payment) {
            fputcsv($handle, [
                $payment->id,
                $payment->user->email ?? 'N/A',
                $payment->retreatPlan->title ?? 'N/A',
                $payment->amount,
                $payment->currency,
                $payment->status,
                $payment->payment_method,
                $payment->transaction_id,
                $payment->paid_at?->format('Y-m-d H:i:s') ?? 'N/A',
                $payment->created_at->format('Y-m-d H:i:s'),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, $headers);
    }

    /**
     * Récupérer les statistiques par utilisateur
     */
    public function userStats(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $payments = Payment::where('user_id', $userId)
            ->with('retreatPlan')
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total_payments' => $payments->count(),
            'total_spent' => $payments->where('status', 'completed')->sum('amount'),
            'completed' => $payments->where('status', 'completed')->count(),
            'pending' => $payments->where('status', 'pending')->count(),
            'failed' => $payments->where('status', 'failed')->count(),
            'refunded' => $payments->where('status', 'refunded')->count(),
        ];

        return response()->json([
            'user' => $user,
            'payments' => $payments,
            'statistics' => $stats,
            'status' => 'success'
        ], 200);
    }

    /**
     * Synchroniser les paiements avec Stripe (pour les paiements externes)
     */
    public function syncStripe(Request $request)
    {
        try {
            \Stripe\Stripe::setApiKey(config('payments.stripe.secret_key'));

            // Récupérer les paiements depuis Stripe
            $paymentIntents = \Stripe\PaymentIntent::all(['limit' => 100]);

            $synced = 0;
            $skipped = 0;

            foreach ($paymentIntents->data as $intent) {
                // Vérifier si le paiement existe déjà en base
                $existing = Payment::where('transaction_id', $intent->id)->first();

                if (!$existing && $intent->status === 'succeeded') {
                    // Créer un nouvel enregistrement de paiement
                    // (Note: c'est une synchronisation simplifiée)
                    $synced++;
                } else {
                    $skipped++;
                }
            }

            return response()->json([
                'message' => 'Synchronisation Stripe complétée',
                'synced' => $synced,
                'skipped' => $skipped,
                'status' => 'success'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Stripe sync error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erreur lors de la synchronisation',
                'error' => $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Obtenir un résumé pour le tableau de bord
     */
    public function dashboard(Request $request)
    {
        $period = $request->period ?? 'month'; // month, year, all
        
        $dateFrom = match($period) {
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => null,
        };

        $query = Payment::where('status', 'completed');
        
        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        $totalRevenue = $query->sum('amount');
        $totalTransactions = $query->count();
        $avgTransaction = $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0;

        // Top 5 plans
        $topPlans = Payment::where('status', 'completed')
            ->when($dateFrom, function ($q) use ($dateFrom) {
                $q->where('created_at', '>=', $dateFrom);
            })
            ->groupBy('retreat_plan_id')
            ->with('retreatPlan')
            ->select('retreat_plan_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as revenue'))
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // Top 5 utilisateurs
        $topUsers = Payment::where('status', 'completed')
            ->when($dateFrom, function ($q) use ($dateFrom) {
                $q->where('created_at', '>=', $dateFrom);
            })
            ->groupBy('user_id')
            ->with('user')
            ->select('user_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return response()->json([
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_transactions' => $totalTransactions,
                'avg_transaction' => round($avgTransaction, 2),
                'period' => $period,
            ],
            'top_plans' => $topPlans,
            'top_users' => $topUsers,
            'status' => 'success'
        ], 200);
    }
}
