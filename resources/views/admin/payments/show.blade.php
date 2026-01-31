@extends('admin.layout')

@section('title', 'Détail du Paiement #' . $payment->id)

@section('content')
<div class="content-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-credit-card"></i> Paiement #{{ $payment->id }}</h1>
    <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<!-- Messages Flash -->
@if($message = Session::get('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> {{ $message }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($message = Session::get('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle"></i> {{ $message }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    <!-- Informations principales -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Informations du Paiement</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>ID de Transaction:</strong><br>
                        <code>{{ $payment->transaction_id ?? 'N/A' }}</code>
                    </div>
                    <div class="col-md-6">
                        <strong>ID de Client Stripe/PayPal:</strong><br>
                        <code>{{ $payment->customer_id ?? 'N/A' }}</code>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Montant:</strong><br>
                        <h4>${{ number_format($payment->amount, 2) }} {{ $payment->currency ?? 'USD' }}</h4>
                    </div>
                    <div class="col-md-6">
                        <strong>Méthode de Paiement:</strong><br>
                        @if($payment->payment_method === 'stripe')
                            <span class="badge bg-info fs-6">Stripe</span>
                        @elseif($payment->payment_method === 'paypal')
                            <span class="badge bg-warning fs-6">PayPal</span>
                        @else
                            <span class="badge bg-secondary fs-6">{{ $payment->payment_method }}</span>
                        @endif
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Statut:</strong><br>
                        @if($payment->status === 'completed')
                            <span class="badge bg-success fs-6">✓ Complété</span>
                        @elseif($payment->status === 'pending')
                            <span class="badge bg-warning fs-6">⏱ En attente</span>
                        @elseif($payment->status === 'failed')
                            <span class="badge bg-danger fs-6">✗ Échoué</span>
                        @elseif($payment->status === 'refunded')
                            <span class="badge bg-info fs-6">↩ Remboursé</span>
                        @else
                            <span class="badge bg-secondary fs-6">{{ $payment->status }}</span>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <strong>Date de Paiement:</strong><br>
                        {{ $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i:s') : 'Non payé' }}
                    </div>
                </div>

                @if($payment->metadata)
                    <div class="row mb-3">
                        <div class="col-12">
                            <strong>Métadonnées:</strong><br>
                            <pre class="bg-light p-3 rounded">{{ json_encode(json_decode($payment->metadata), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Informations utilisateur et plan -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Utilisateur</h5>
            </div>
            <div class="card-body">
                <p><strong>Nom:</strong> {{ $payment->user->name ?? 'N/A' }}</p>
                <p><strong>Email:</strong> {{ $payment->user->email ?? 'N/A' }}</p>
                <p><strong>Téléphone:</strong> {{ $payment->user->phone ?? 'N/A' }}</p>
                <a href="{{ route('admin.payments.user-stats', $payment->user_id) }}" class="btn btn-sm btn-info">
                    Voir tous les paiements
                </a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Plan de Retraite</h5>
            </div>
            <div class="card-body">
                <p><strong>Titre:</strong> {{ $payment->retreatPlan->title ?? 'N/A' }}</p>
                <p><strong>Prix:</strong> ${{ $payment->retreatPlan->price ?? 'N/A' }}</p>
                <p><strong>Durée:</strong> {{ $payment->retreatPlan->duration_days ?? 'N/A' }} jours</p>
            </div>
        </div>

        <!-- Actions -->
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Actions</h5>
            </div>
            <div class="card-body">
                @if($payment->status !== 'completed')
                    <form action="{{ route('admin.payments.update-status', $payment->id) }}" method="POST" class="mb-3">
                        @csrf
                        <select name="status" class="form-select mb-2">
                            <option value="">Changer le statut...</option>
                            <option value="completed">Marquer comme complété</option>
                            <option value="pending">Marquer comme en attente</option>
                            <option value="failed">Marquer comme échoué</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="bi bi-check"></i> Mettre à jour
                        </button>
                    </form>
                @endif

                @if($payment->status === 'completed')
                    <form action="{{ route('admin.payments.refund', $payment->id) }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label">Raison du remboursement</label>
                            <textarea name="reason" class="form-control" rows="3" placeholder="Expliquez pourquoi..."></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Montant à rembourser</label>
                            <input type="number" name="amount" class="form-control" step="0.01" 
                                   value="{{ $payment->amount }}" max="{{ $payment->amount }}">
                        </div>
                        <button type="submit" class="btn btn-sm btn-danger w-100" 
                                onclick="return confirm('Êtes-vous sûr de vouloir rembourser ce paiement?')">
                            <i class="bi bi-arrow-counterclockwise"></i> Rembourser
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .content-header {
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #e9ecef;
    }

    .content-header h1 {
        margin: 0;
        color: #333;
    }

    .card {
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .card-header {
        border-bottom: 1px solid #e9ecef;
    }

    code {
        background-color: #f8f9fa;
        padding: 0.2rem 0.4rem;
        border-radius: 0.25rem;
        font-size: 0.875rem;
    }

    pre {
        margin-bottom: 0;
        font-size: 0.875rem;
    }
</style>
@endsection
