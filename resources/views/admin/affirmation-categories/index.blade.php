@extends('admin.layout')

@section('title', 'Catégories d\'affirmations')

@section('content')
<div class="content-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-tags"></i> Catégories d'affirmations</h1>
    <a href="{{ route('admin.affirmation-categories.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Nouvelle catégorie
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card">
    <div class="card-body">
        @if($categories->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Ordre</th>
                            <th>Nom</th>
                            <th>Description</th>
                            <th>Affirmations</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $cat)
                            <tr>
                                <td>{{ $cat->order }}</td>
                                <td><strong>{{ $cat->name }}</strong></td>
                                <td>{{ Str::limit($cat->description, 40) }}</td>
                                <td>{{ $cat->affirmations_count }}</td>
                                <td>
                                    @if($cat->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.affirmation-categories.edit', $cat) }}" class="btn btn-warning" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.affirmation-categories.destroy', $cat) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette catégorie ? Les affirmations devront être réassignées.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-4">
                {{ $categories->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-tags" style="font-size: 64px; color: #ccc;"></i>
                <p class="text-muted mt-3">Aucune catégorie. Créez-en pour les utiliser dans les affirmations.</p>
                <a href="{{ route('admin.affirmation-categories.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Créer une catégorie
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
