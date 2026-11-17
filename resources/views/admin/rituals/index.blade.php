@extends('admin.layout')

@section('title', 'Espace Sacré des Lois')

@section('content')
<div class="content-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h1><i class="bi bi-flower1"></i> Espace Sacré des Lois — tutoriels</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.ritual-categories.index') }}" class="btn btn-secondary"><i class="bi bi-tags"></i> Catégories</a>
        <a href="{{ route('admin.rituals.create', request()->only('category')) }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nouveau tutoriel</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-5">
                <select name="category" class="form-select" onchange="this.form.submit()">
                    <option value="">Toutes les catégories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) request('category') === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Tutoriel</th><th>Catégorie</th><th>Étapes</th><th>Durée</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($rituals as $ritual)
                    <tr>
                        <td>
                            <a href="{{ route('admin.rituals.edit', $ritual) }}"><strong>{{ $ritual->title }}</strong></a><br>
                            <small class="text-muted">{{ Str::limit($ritual->summary, 90) }}</small>
                        </td>
                        <td>{{ $ritual->category?->name }}</td>
                        <td>{{ count($ritual->steps ?? []) }}</td>
                        <td>{{ $ritual->duration_minutes ? $ritual->duration_minutes . ' min' : '—' }}</td>
                        <td>@if($ritual->is_published)<span class="badge bg-success">Publié</span>@else<span class="badge bg-secondary">Brouillon</span>@endif</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.rituals.edit', $ritual) }}" class="btn btn-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('admin.rituals.destroy', $ritual) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce tutoriel ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">Aucun tutoriel pour le moment. Chaque tutoriel présente ses objectifs, le matériel, les étapes, les précautions et les traditions auxquelles il se rattache.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">{{ $rituals->links() }}</div>
    </div>
</div>
@endsection
