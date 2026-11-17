@extends('admin.layout')

@section('title', 'Oracles')

@section('content')
<div class="content-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-suit-diamond"></i> Oracles — jeu de cartes</h1>
    <a href="{{ route('admin.oracle-cards.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nouvelle carte</a>
</div>

<div class="row mb-4">
    <div class="col-md-4"><div class="stat-card"><p class="stat-value">{{ $stats['active'] }}</p><p class="stat-label">Cartes actives</p></div></div>
    <div class="col-md-4"><div class="stat-card"><p class="stat-value">{{ $stats['draws_month'] }}</p><p class="stat-label">Tirages ce mois-ci</p></div></div>
    <div class="col-md-4"><div class="stat-card"><p class="stat-value">{{ $stats['users_month'] }}</p><p class="stat-label">Utilisateurs ayant tiré ce mois-ci</p></div></div>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle"></i>
    Règles appliquées par le serveur : un tirage tous les 3 jours, une carte jamais tirée deux fois par le même utilisateur dans le même mois, nouveau cycle chaque mois.
    Gardez au moins <strong>11 cartes actives</strong> (nombre maximal de tirages par mois).
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr><th>Image</th><th>Nom</th><th>Message</th><th>Tirages</th><th>Statut</th><th>Actions</th></tr>
                </thead>
                <tbody>
                @forelse($cards as $card)
                    <tr>
                        <td>
                            @if($card->image_url)
                                <img src="{{ $card->image_url }}" alt="" style="width:48px;height:72px;object-fit:cover;border-radius:6px;">
                            @else
                                <span class="text-muted"><i class="bi bi-image" style="font-size:28px;"></i></span>
                            @endif
                        </td>
                        <td><strong>{{ $card->name }}</strong><br><small class="text-muted">{{ $card->keywords }}</small></td>
                        <td>{{ Str::limit($card->message, 70) }}</td>
                        <td>{{ $card->draws_count }}</td>
                        <td>
                            @if($card->is_active)<span class="badge bg-success">Active</span>@else<span class="badge bg-secondary">Inactive</span>@endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.oracle-cards.edit', $card) }}" class="btn btn-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('admin.oracle-cards.destroy', $card) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette carte ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">Aucune carte.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">{{ $cards->links() }}</div>
    </div>
</div>
@endsection
