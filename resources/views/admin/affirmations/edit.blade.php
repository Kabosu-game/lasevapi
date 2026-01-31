@extends('admin.layout')

@section('title', 'Modifier l\'Affirmation')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-quote"></i> Modifier l'Affirmation</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.affirmations.update', $affirmation) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="mb-3">
                <label for="category_id" class="form-label">Catégorie <span class="text-danger">*</span></label>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required style="max-width: 300px;">
                        <option value="">Sélectionner une catégorie</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $affirmation->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    <a href="{{ route('admin.affirmation-categories.index') }}" class="btn btn-outline-primary btn-sm" target="_blank">
                        <i class="bi bi-tags"></i> Gérer les catégories
                    </a>
                </div>
                @error('category_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="title" class="form-label">Titre <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('title') is-invalid @enderror" 
                       id="title" name="title" value="{{ old('title', $affirmation->title) }}" required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="body" class="form-label">Contenu <span class="text-danger">*</span></label>
                <textarea class="form-control @error('body') is-invalid @enderror" 
                          id="body" name="body" rows="6" required>{{ old('body', $affirmation->body) }}</textarea>
                @error('body')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.affirmations.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Annuler
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

