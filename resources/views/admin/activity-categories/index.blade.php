@extends('admin.layout')

@section('title', 'Activités — catégories')

@section('content')
<div class="content-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h1><i class="bi bi-headphones"></i> Activités — catégories</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.breathing-patterns.index') }}" class="btn btn-secondary"><i class="bi bi-wind"></i> Exercices de respiration</a>
        <a href="{{ route('admin.activity-categories.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nouvelle catégorie</a>
    </div>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> Les catégories de l'espace Activités de l'app. Les contenus (audio, vidéo) se rangent dans une catégorie depuis la page
    <a href="{{ route('admin.meditations.index') }}">Méditations</a>.
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Ordre</th><th>Catégorie</th><th>Clé</th><th>Contenus</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td>{{ $category->sort_order }}</td>
                        <td>
                            <span class="d-inline-block rounded-circle align-middle me-2" style="width:14px;height:14px;background:{{ $category->color }}"></span>
                            <strong>{{ $category->name }}</strong>
                            @if($category->with_breathing_exercises)<span class="badge bg-info text-dark ms-1">+ respiration guidée</span>@endif
                            <br><small class="text-muted">{{ $category->description }}</small>
                        </td>
                        <td><code>{{ $category->key }}</code></td>
                        <td>{{ $category->meditations_count }}</td>
                        <td>@if($category->is_active)<span class="badge bg-success">Visible</span>@else<span class="badge bg-secondary">Masquée</span>@endif</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.activity-categories.edit', $category) }}" class="btn btn-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('admin.activity-categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette catégorie ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">Aucune catégorie.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
