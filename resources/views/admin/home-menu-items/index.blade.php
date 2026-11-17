@extends('admin.layout')

@section('title', 'Menus page d\'accueil')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-grid-3x3-gap"></i> Menus page d'accueil</h1>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> Ces rubriques s'affichent sur la page d'accueil de l'app et dans l'onglet « Plus ».
    Vous pouvez modifier le nom, l'image, l'ordre d'affichage et masquer une rubrique.
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Ordre</th>
                        <th>Image</th>
                        <th>Nom affiché</th>
                        <th>Slug</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr class="{{ $item->isLegacy() ? 'text-muted' : '' }}">
                            <td>{{ $item->sort_order }}</td>
                            <td>
                                @if($item->image)
                                    <img src="{{ url('serve-storage/' . $item->image) }}" alt="{{ $item->name }}"
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                @else
                                    <span class="badge bg-secondary">Icône par défaut</span>
                                @endif
                            </td>
                            <td><strong>{{ $item->name }}</strong></td>
                            <td><code>{{ $item->slug }}</code></td>
                            <td>
                                @if($item->isLegacy())
                                    <span class="badge bg-light text-dark" title="Remplacée par « {{ \App\Models\HomeMenuItem::LEGACY[$item->slug] }} »">Ancienne rubrique</span>
                                @elseif($item->is_visible)
                                    <span class="badge bg-success">Visible</span>
                                @else
                                    <span class="badge bg-secondary">Masquée</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.home-menu-items.edit', $item) }}" class="btn btn-warning btn-sm" title="Modifier">
                                    <i class="bi bi-pencil"></i> Modifier
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <small class="text-muted">
            Les anciennes rubriques (Affirmation, Meditation, Gratitude) ont été remplacées par Oracles, Activités et My Journal.
            Elles restent ici pour conserver leurs images mais ne s'affichent plus dans l'app.
        </small>
    </div>
</div>
@endsection
