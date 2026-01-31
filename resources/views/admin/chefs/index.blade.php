@extends('admin.layout')

@section('title', 'Chefs cuisiniers')

@section('content')
<div class="content-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-person-badge"></i> Nos chefs cuisiniers</h1>
    <a href="{{ route('admin.chefs.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Nouveau chef
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-body">
        @if($chefs->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Nom</th>
                            <th>Rôle / Titre</th>
                            <th>Ordre</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($chefs as $chef)
                            <tr>
                                <td>
                                    @if($chef->image)
                                        <img src="{{ url('serve-storage/' . $chef->image) }}" alt="{{ $chef->name }}"
                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                    @else
                                        <span class="badge bg-secondary">Aucune</span>
                                    @endif
                                </td>
                                <td><strong>{{ $chef->name }}</strong></td>
                                <td>{{ $chef->role ?? '—' }}</td>
                                <td>{{ $chef->sort_order }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.chefs.edit', $chef) }}" class="btn btn-warning" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.chefs.destroy', $chef) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce chef ?');">
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
                {{ $chefs->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-person-badge" style="font-size: 64px; color: #ccc;"></i>
                <p class="text-muted mt-3">Aucun chef. Les chefs ajoutés ici s'affichent dans l'app (page Cuisine).</p>
                <a href="{{ route('admin.chefs.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Ajouter un chef
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
