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
