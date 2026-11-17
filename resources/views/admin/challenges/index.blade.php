@extends('admin.layout')

@section('title', 'Défis du mois')

@php
    $statusBadges = [
        'draft' => ['secondary', 'Brouillon'],
        'scheduled' => ['info', 'Programmé'],
        'active' => ['success', 'En cours'],
        'ended' => ['warning', 'Terminé (à clôturer)'],
        'closed' => ['dark', 'Clôturé'],
    ];
@endphp

@section('content')
<div class="content-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-trophy"></i> Défis du mois</h1>
    <a href="{{ route('admin.challenges.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nouveau défi</a>
</div>

<div class="alert alert-info">
    <i class="bi bi-arrow-repeat"></i>
    Les défis marqués « renouvelé chaque mois » sont clôturés automatiquement à leur date de fin (classement figé) puis recréés pour le mois suivant.
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr><th>Défi</th><th>Type</th><th>Période</th><th>Objectif</th><th>Participants</th><th>Statut</th><th>Actions</th></tr>
                </thead>
                <tbody>
                @forelse($challenges as $challenge)
                    @php([$color, $label] = $statusBadges[$challenge->status])
                    <tr>
                        <td>
                            <a href="{{ route('admin.challenges.show', $challenge) }}"><strong>{{ $challenge->title }}</strong></a>
                            @if($challenge->recurring_monthly)<i class="bi bi-arrow-repeat text-muted" title="Renouvelé chaque mois"></i>@endif
                            <br><small class="text-muted">{{ $challenge->objective }}</small>
                        </td>
                        <td>{{ \App\Models\Challenge::TYPES[$challenge->type] ?? $challenge->type }}</td>
                        <td>{{ $challenge->starts_at->format('d/m/Y') }} → {{ $challenge->ends_at->format('d/m/Y') }}</td>
                        <td>{{ $challenge->target_days }} jours</td>
                        <td>{{ $challenge->participants_count }}</td>
                        <td><span class="badge bg-{{ $color }}">{{ $label }}</span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.challenges.show', $challenge) }}" class="btn btn-info" title="Classement"><i class="bi bi-list-ol"></i></a>
                                <a href="{{ route('admin.challenges.edit', $challenge) }}" class="btn btn-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('admin.challenges.destroy', $challenge) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce défi et sa progression ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">Aucun défi pour le moment.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">{{ $challenges->links() }}</div>
    </div>
</div>
@endsection
