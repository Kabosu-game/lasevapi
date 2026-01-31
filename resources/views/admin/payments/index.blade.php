@extends('admin.layout')

@section('title', 'Historique des Paiements')

@section('content')
<div class="content-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-credit-card"></i> Historique des Paiements</h1>
    <div>
        <a href="{{ route('admin.payments.statistics') }}" class="btn btn-info me-2">
            <i class="bi bi-bar-chart"></i> Statistiques
        </a>
        <a href="{{ route('admin.payments.export') }}" class="btn btn-success">
            <i class="bi bi-download"></i> Exporter CSV
        </a>
    </div>
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

<div class="card">
    <div class="card-body">
        <!-- Filtres -->
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-2">
                <select name="status" class="form-control">
                    <option value="">Tous les statuts</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Complété</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Échoué</option>
                    <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Remboursé</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="payment_method" class="form-control">
                    <option value="">Toutes les méthodes</option>
                    <option value="stripe" {{ request('payment_method') === 'stripe' ? 'selected' : '' }}>Stripe</option>
                    <option value="paypal" {{ request('payment_method') === 'paypal' ? 'selected' : '' }}>PayPal</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="retreat_plan_id" class="form-control">
                    <option value="">Tous les plans</option>
                    @foreach($retreatPlans as $plan)
                        <option value="{{ $plan->id }}" {{ request('retreat_plan_id') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->title }} (${{ $plan->price }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="De">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="À">
            </div>
            <div class="col-md-2">
                <input type="text" name="search" class="form-control" placeholder="Email ou nom" value="{{ request('search') }}">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filtrer
                </button>
                <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-clockwise"></i> Réinitialiser
                </a>
            </div>
        </form>

        @if($payments->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Utilisateur</th>
                            <th>Plan</th>
                            <th>Montant</th>
                            <th>Méthode</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $payment)
                            <tr>
                                <td><strong>#{{ $payment->id }}</strong></td>
                                <td>
                                    <small>{{ $payment->user->name ?? 'N/A' }}</small><br>
                                    <small class="text-muted">{{ $payment->user->email ?? 'N/A' }}</small>
                                </td>
                                <td>{{ $payment->retreatPlan->title ?? 'N/A' }}</td>
                                <td><strong>${{ number_format($payment->amount, 2) }}</strong></td>
                                <td>
                                    @if($payment->payment_method === 'stripe')
                                        <span class="badge bg-info">Stripe</span>
                                    @elseif($payment->payment_method === 'paypal')
                                        <span class="badge bg-warning">PayPal</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $payment->payment_method }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($payment->status === 'completed')
                                        <span class="badge bg-success">✓ Complété</span>
                                    @elseif($payment->status === 'pending')
                                        <span class="badge bg-warning">⏱ En attente</span>
                                    @elseif($payment->status === 'failed')
                                        <span class="badge bg-danger">✗ Échoué</span>
                                    @elseif($payment->status === 'refunded')
                                        <span class="badge bg-info">↩ Remboursé</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $payment->status }}</span>
                                    @endif
                                </td>
                                <td><small>{{ $payment->created_at->format('d/m/Y H:i') }}</small></td>
                                <td>
                                    <a href="{{ route('admin.payments.show', $payment->id) }}" class="btn btn-sm btn-info" title="Détails">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $payments->links() }}
            </div>
        @else
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Aucun paiement trouvé.
            </div>
        @endif
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
</style>
@endsection
