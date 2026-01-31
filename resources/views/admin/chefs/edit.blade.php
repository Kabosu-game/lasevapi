@extends('admin.layout')

@section('title', 'Modifier le chef')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-person-badge"></i> Modifier le chef</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.chefs.update', $chef) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="name" class="form-label">Nom <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $chef->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Rôle / Titre</label>
                <input type="text" class="form-control @error('role') is-invalid @enderror" id="role" name="role" value="{{ old('role', $chef->role) }}">
                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">Photo</label>
                @if($chef->image)
                    <div class="mb-2">
                        <img src="{{ url('serve-storage/' . $chef->image) }}" alt="{{ $chef->name }}" style="max-height: 120px; border-radius: 8px;">
                    </div>
                @endif
                <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/*">
                @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="sort_order" class="form-label">Ordre d'affichage</label>
                <input type="number" class="form-control" id="sort_order" name="sort_order" value="{{ old('sort_order', $chef->sort_order) }}" min="0">
            </div>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="{{ route('admin.chefs.index') }}" class="btn btn-secondary">Annuler</a>
        </form>
    </div>
</div>
@endsection
