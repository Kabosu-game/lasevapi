@extends('admin.layout')

@section('title', $challenge->title)

@section('content')
<div class="content-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="bi bi-trophy"></i> {{ $challenge->title }}</h1>
        <small class="text-muted">
            {{ $challenge->objective }} — {{ $challenge->starts_at->format('d/m/Y') }} → {{ $challenge->ends_at->format('d/m/Y') }}
            — {{ $challenge->target_days }} jours, {{ $challenge->points_per_day }} pts/jour, bonus {{ $challenge->completion_bonus }} pts
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.challenges.edit', $challenge) }}" class="btn btn-warning"><i class="bi bi-pencil"></i> Modifier</a>
        @unless($challenge->closed_at)
            <form action="{{ route('admin.challenges.close', $challenge) }}" method="POST" onsubmit="return confirm('Clôturer ce défi ? Le classement sera figé et les validations arrêtées.');">
                @csrf
                <button type="submit" class="btn btn-dark"><i class="bi bi-flag"></i> Clôturer</button>
            </form>
        @endunless
    </div>
</div>

@if($challenge->closed_at)
    <div class="alert alert-secondary"><i class="bi bi-lock"></i> Défi clôturé le {{ $challenge->closed_at->format('d/m/Y à H:i') }}. Classement final figé.</div>
@endif
@if($challenge->reward)
    <div class="alert alert-warning"><i class="bi bi-gift"></i> <strong>Récompense :</strong> {{ $challenge->reward }}</div>
@endif

<div class="card">
    <div class="card-header">Classement des participants</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr><th>Rang</th><th>Participant</th><th>Jours accomplis</th><th>Points</th><th>Réussi</th><th>Récompense</th></tr>
                </thead>
                <tbody>
                @forelse($participants as $i => $p)
                    <tr>
                        <td><strong>{{ $p->final_rank ?? ($participants->firstItem() + $i) }}</strong></td>
                        <td>{{ $p->user?->name ?? '—' }} <small class="text-muted">#{{ $p->user_id }}</small></td>
                        <td>
                            {{ $p->days_completed }} / {{ $challenge->target_days }}
                            <div class="progress" style="height:6px;width:120px;">
                                <div class="progress-bar bg-success" style="width: {{ min(100, round($p->days_completed / max(1, $challenge->target_days) * 100)) }}%"></div>
                            </div>
                        </td>
                        <td>{{ $p->points }}</td>
                        <td>@if($p->completed_at)<span class="badge bg-success">{{ $challenge->badge_name ?: 'Oui' }}</span>@else<span class="text-muted">—</span>@endif</td>
                        <td>
                            <form action="{{ route('admin.challenges.reward', [$challenge, $p]) }}" method="POST">
                                @csrf
                                @if($p->reward_granted)
                                    <button class="btn btn-sm btn-success"><i class="bi bi-gift-fill"></i> Attribuée</button>
                                @else
                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-gift"></i> Attribuer</button>
                                @endif
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">Aucun participant pour le moment.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">{{ $participants->links() }}</div>
    </div>
</div>
@endsection
