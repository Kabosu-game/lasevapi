@extends('admin.layout')

@section('title', 'Nouvelle catégorie d\'affirmation')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-tags"></i> Nouvelle catégorie d'affirmation</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.affirmation-categories.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label">Nom <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="color" class="form-label">Couleur (hex)</label>
                        <input type="text" class="form-control" id="color" name="color" value="{{ old('color') }}" placeholder="#265533">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="order" class="form-label">Ordre</label>
                        <input type="number" class="form-control" id="order" name="order" value="{{ old('order', 0) }}" min="0">
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Créer</button>
            <a href="{{ route('admin.affirmation-categories.index') }}" class="btn btn-secondary">Annuler</a>
        </form>
    </div>
</div>
@endsection
