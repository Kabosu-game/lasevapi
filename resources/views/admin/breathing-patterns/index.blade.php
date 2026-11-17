@extends('admin.layout')

@section('title', 'Exercices de respiration')

@section('content')
<div class="content-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h1><i class="bi bi-wind"></i> Exercices de respiration</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.activity-categories.index') }}" class="btn btn-secondary"><i class="bi bi-headphones"></i> Catégories d'activités</a>
        <a href="{{ route('admin.breathing-patterns.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nouvel exercice</a>
    </div>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> Exercices guidés proposés dans l'app (animation du souffle + minuteur).
    Durées en secondes : inspiration, rétention poumons pleins, expiration, pause poumons vides.
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Ordre</th><th>Exercice</th><th>Rythme</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($patterns as $pattern)
                    <tr>
                        <td>{{ $pattern->sort_order }}</td>
                        <td><strong>{{ $pattern->name }}</strong><br><small class="text-muted">{{ $pattern->description }}</small></td>
                        <td>
                            <code>{{ $pattern->rhythm }}</code>
                            <br><small class="text-muted">
                                Inspire {{ $pattern->inhale_seconds }} s
                                @if($pattern->hold_in_seconds) · retiens {{ $pattern->hold_in_seconds }} s @endif
                                · expire {{ $pattern->exhale_seconds }} s
                                @if($pattern->hold_out_seconds) · pause {{ $pattern->hold_out_seconds }} s @endif
                            </small>
                        </td>
                        <td>@if($pattern->is_active)<span class="badge bg-success">Visible</span>@else<span class="badge bg-secondary">Masqué</span>@endif</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.breathing-patterns.edit', $pattern) }}" class="btn btn-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('admin.breathing-patterns.destroy', $pattern) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cet exercice ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">Aucun exercice.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
