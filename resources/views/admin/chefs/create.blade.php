@extends('admin.layout')

@section('title', 'Nouveau chef')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-person-badge"></i> Nouveau chef cuisinier</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.chefs.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label">Nom <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Rôle / Titre</label>
                <input type="text" class="form-control @error('role') is-invalid @enderror" id="role" name="role" value="{{ old('role') }}" placeholder="ex: Chef exécutif, Chef pâtissier">
                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">Photo</label>
                <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/*">
                @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="sort_order" class="form-label">Ordre d'affichage</label>
                <input type="number" class="form-control" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
            </div>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="{{ route('admin.chefs.index') }}" class="btn btn-secondary">Annuler</a>
        </form>
    </div>
</div>
@endsection
