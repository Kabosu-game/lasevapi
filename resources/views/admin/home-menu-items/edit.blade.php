@extends('admin.layout')

@section('title', 'Modifier le menu')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-grid-3x3-gap"></i> Modifier : {{ $item->name }}</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.home-menu-items.update', $item) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">Slug (fixe)</label>
                <input type="text" class="form-control" value="{{ $item->slug }}" disabled>
            </div>
            <div class="mb-3">
                <label for="name" class="form-label">Nom affiché sur l'app <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $item->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="sort_order" class="form-label">Ordre d'affichage</label>
                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" min="0" max="999" value="{{ old('sort_order', $item->sort_order) }}">
                    @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Les rubriques s'affichent de la plus petite à la plus grande valeur.</small>
                </div>
                <div class="col-md-8 mb-3 d-flex align-items-center">
                    @if($item->isLegacy())
                        <div class="alert alert-light mb-0 w-100">
                            <i class="bi bi-info-circle"></i> Ancienne rubrique, remplacée par « {{ \App\Models\HomeMenuItem::LEGACY[$item->slug] }} » : elle ne s'affiche plus dans l'app.
                        </div>
                    @else
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_visible" value="0">
                            <input class="form-check-input" type="checkbox" id="is_visible" name="is_visible" value="1" @checked(old('is_visible', $item->is_visible))>
                            <label class="form-check-label" for="is_visible">Visible dans l'app</label>
                        </div>
                    @endif
                </div>
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">Image du menu (page d'accueil)</label>
                @if($item->image)
                    <div class="mb-2">
                        <img src="{{ url('serve-storage/' . $item->image) }}" alt="{{ $item->name }}" style="max-height: 120px; border-radius: 8px;">
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="remove_image" name="remove_image" value="1">
                        <label class="form-check-label" for="remove_image">Supprimer l'image actuelle</label>
                    </div>
                @endif
                <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/*">
                <small class="text-muted">JPEG, PNG, JPG ou GIF, max 2 Mo. L'image s'affiche sur la page d'accueil de l'app pour ce menu.</small>
                @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="{{ route('admin.home-menu-items.index') }}" class="btn btn-secondary">Annuler</a>
        </form>
    </div>
</div>
@endsection
