@extends('admin.layout')

@section('title', 'Espace Sacré des Lois — catégories')

@section('content')
<div class="content-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h1><i class="bi bi-flower1"></i> Espace Sacré des Lois — catégories</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.rituals.index') }}" class="btn btn-secondary"><i class="bi bi-journal-richtext"></i> Tutoriels</a>
        <a href="{{ route('admin.ritual-categories.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nouvelle catégorie</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Ordre</th><th>Catégorie</th><th>Tutoriels</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td>{{ $category->sort_order }}</td>
                        <td><strong>{{ $category->name }}</strong><br><small class="text-muted">{{ $category->description }}</small></td>
                        <td><a href="{{ route('admin.rituals.index', ['category' => $category->id]) }}">{{ $category->rituals_count }}</a></td>
                        <td>@if($category->is_active)<span class="badge bg-success">Active</span>@else<span class="badge bg-secondary">Masquée</span>@endif</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.ritual-categories.edit', $category) }}" class="btn btn-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('admin.ritual-categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette catégorie ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">Aucune catégorie.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
