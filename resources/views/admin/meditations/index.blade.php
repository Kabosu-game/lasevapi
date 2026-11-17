@extends('admin.layout')

@section('title', 'Gestion des Méditations')

@section('content')
<div class="content-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-music-note-list"></i> Gestion des Méditations</h1>
    <a href="{{ route('admin.meditations.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Nouvelle méditation
    </a>
</div>

<div class="card">
    <div class="card-body">
        @php($categoryNames = \App\Models\ActivityCategory::options())
        @if($meditations->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titre</th>
                            <th>Catégorie</th>
                            <th>Slug</th>
                            <th>Durée</th>
                            <th>Media</th>
                            <th>Créé le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($meditations as $meditation)
                            <tr>
                                <td>{{ $meditation->id }}</td>
                                <td>{{ $meditation->title }}</td>
                                <td><span class="badge bg-light text-dark">{{ $categoryNames[$meditation->category] ?? $meditation->category }}</span></td>
                                <td><code>{{ $meditation->slug ?? 'N/A' }}</code></td>
                                <td>{{ $meditation->duration ? gmdate('i:s', $meditation->duration) : 'N/A' }}</td>
                                <td>
                                    @if($meditation->media)
                                        <span class="badge bg-success">
                                            <i class="bi bi-file-earmark-music"></i> Audio
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">Aucun</span>
                                    @endif
                                </td>
                                <td>{{ $meditation->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.meditations.show', $meditation) }}" class="btn btn-info" title="Voir">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.meditations.edit', $meditation) }}" class="btn btn-warning" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.meditations.destroy', $meditation) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette méditation ?');">
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
                {{ $meditations->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-music-note-list" style="font-size: 64px; color: #ccc;"></i>
                <p class="text-muted mt-3">Aucune méditation trouvée.</p>
                <a href="{{ route('admin.meditations.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Créer une méditation
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

