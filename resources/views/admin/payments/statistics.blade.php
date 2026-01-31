@extends('admin.layout')

@section('title', 'Statistiques des Paiements')

@section('content')
<div class="content-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-bar-chart"></i> Statistiques des Paiements</h1>
    <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<!-- Filtres de date -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Date de début</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from', now()->subDays(30)->format('Y-m-d')) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Date de fin</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to', now()->format('Y-m-d')) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filtrer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Cartes de statistiques -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="stat-value text-success">
                    ${{ number_format($stats['total_revenue'] ?? 0, 2) }}
                </div>
                <div class="stat-label">Revenus Totaux</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="stat-value text-info">
                    {{ $stats['total_payments'] ?? 0 }}
                </div>
                <div class="stat-label">Total Paiements</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="stat-value text-success">
                    {{ $stats['completed_payments'] ?? 0 }}
                </div>
                <div class="stat-label">Paiements Complétés</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="stat-value text-warning">
                    {{ $stats['pending_payments'] ?? 0 }}
                </div>
                <div class="stat-label">Paiements En Attente</div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="stat-value text-danger">
                    {{ $stats['failed_payments'] ?? 0 }}
                </div>
                <div class="stat-label">Paiements Échoués</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="stat-value">
                    @if($stats['total_payments'] > 0)
                        ${{ number_format($stats['total_revenue'] / $stats['total_payments'], 2) }}
                    @else
                        $0.00
                    @endif
                </div>
                <div class="stat-label">Montant Moyen</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="stat-value">
                    @if($stats['total_payments'] > 0)
                        {{ round(($stats['completed_payments'] / $stats['total_payments']) * 100) }}%
                    @else
                        0%
                    @endif
                </div>
                <div class="stat-label">Taux de Réussite</div>
            </div>
        </div>
    </div>
</div>

<!-- Graphiques par méthode et plan -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Paiements par Méthode</h5>
            </div>
            <div class="card-body">
                @if(isset($stats['by_method']) && count($stats['by_method']) > 0)
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Méthode</th>
                                <th>Nombre</th>
                                <th>Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['by_method'] as $method => $data)
                                <tr>
                                    <td>
                                        @if($method === 'stripe')
                                            <span class="badge bg-info">Stripe</span>
                                        @elseif($method === 'paypal')
                                            <span class="badge bg-warning">PayPal</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $method }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $data['count'] ?? 0 }}</td>
                                    <td>${{ number_format($data['total'] ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-muted text-center">Aucune donnée</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Paiements par Plan</h5>
            </div>
            <div class="card-body">
                @if(isset($stats['by_plan']) && count($stats['by_plan']) > 0)
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Plan</th>
                                <th>Nombre</th>
                                <th>Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['by_plan'] as $plan => $data)
                                <tr>
                                    <td>{{ $plan }}</td>
                                    <td>{{ $data['count'] ?? 0 }}</td>
                                    <td>${{ number_format($data['total'] ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-muted text-center">Aucune donnée</p>
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

    .stat-card .stat-value {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }

    .stat-card .stat-label {
        font-size: 0.875rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .card-header {
        border-bottom: 1px solid #e9ecef;
    }
</style>
@endsection
