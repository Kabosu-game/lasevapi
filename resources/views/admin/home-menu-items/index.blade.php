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
    <i class="bi bi-info-circle"></i> Ces menus s'affichent sur la page d'accueil de l'app (Affirmation, Meditation, etc.). Vous pouvez modifier le nom et l'image de chaque entrée.
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Slug</th>
                        <th>Nom affiché</th>
                        <th>Ordre</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>
                                @if($item->image)
                                    <img src="{{ url('serve-storage/' . $item->image) }}" alt="{{ $item->name }}"
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                @else
                                    <span class="badge bg-secondary">Aucune</span>
                                @endif
                            </td>
                            <td><code>{{ $item->slug }}</code></td>
                            <td><strong>{{ $item->name }}</strong></td>
                            <td>{{ $item->sort_order }}</td>
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
    </div>
</div>
@endsection
