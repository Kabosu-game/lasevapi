@extends('admin.layout')

@section('title', 'Modifier le plat')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-egg-fried"></i> Modifier le plat</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.dishes.update', $dish) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="name" class="form-label">Nom du plat <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $dish->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">Image</label>
                @if($dish->image)
                    <div class="mb-2">
                        <img src="{{ url('serve-storage/' . $dish->image) }}" alt="{{ $dish->name }}" style="max-height: 120px; border-radius: 8px;">
                    </div>
                @endif
                <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/*">
                @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="sort_order" class="form-label">Ordre d'affichage</label>
                <input type="number" class="form-control" id="sort_order" name="sort_order" value="{{ old('sort_order', $dish->sort_order) }}" min="0">
            </div>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="{{ route('admin.dishes.index') }}" class="btn btn-secondary">Annuler</a>
        </form>
    </div>
</div>
@endsection
