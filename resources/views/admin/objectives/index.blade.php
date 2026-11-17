@extends('admin.layout')

@section('title', 'Objectifs (inscription)')

@section('content')
<div class="content-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h1><i class="bi bi-bullseye"></i> Objectifs proposés à l'inscription</h1>
    <a href="{{ route('admin.objectives.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nouvel objectif</a>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> Liste affichée lors de la création du profil dans l'app. Les objectifs choisis guident aussi l'Assistant IA.
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Ordre</th><th>Objectif</th><th>Comptes l'ayant choisi</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($objectives as $objective)
                    <tr>
                        <td>{{ $objective->sort_order }}</td>
                        <td><strong>{{ $objective->name }}</strong></td>
                        <td>{{ $usage[$objective->id] ?? 0 }}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.objectives.edit', $objective) }}" class="btn btn-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('admin.objectives.destroy', $objective) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cet objectif ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-5">Aucun objectif.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
