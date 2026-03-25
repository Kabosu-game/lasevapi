@extends('admin.layout')

@section('title', 'Nouvel Article de Blog')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-journal-text"></i> Nouvel Article de Blog</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.blogs.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="mb-3">
                <label for="title" class="form-label">Titre <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('title') is-invalid @enderror" 
                       id="title" name="title" value="{{ old('title') }}" required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                <textarea class="form-control @error('description') is-invalid @enderror" 
                          id="description" name="description" rows="3" required>{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="body" class="form-label">Contenu <span class="text-danger">*</span></label>
                <textarea class="form-control @error('body') is-invalid @enderror" 
                          id="body" name="body" rows="10" required>{{ old('body') }}</textarea>
                @error('body')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="images" class="form-label">Photos de l'article (galerie)</label>
                <input class="form-control @error('images') is-invalid @enderror"
                       type="file"
                       id="images"
                       name="images[]"
                       multiple
                       accept="image/*">
                <small class="form-text text-muted">Optionnel. Jusqu'à 10 images.</small>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="category" class="form-label">Catégorie</label>
                        <select class="form-select @error('category') is-invalid @enderror" id="category" name="category">
                            <option value="general" {{ old('category', 'general') == 'general' ? 'selected' : '' }}>Général</option>
                            <option value="pouvoir-secret" {{ old('category') == 'pouvoir-secret' ? 'selected' : '' }}>Le pouvoir secret</option>
                            <option value="meditation" {{ old('category') == 'meditation' ? 'selected' : '' }}>Méditation</option>
                            <option value="bien-etre" {{ old('category') == 'bien-etre' ? 'selected' : '' }}>Bien-être</option>
                            <option value="developpement" {{ old('category') == 'developpement' ? 'selected' : '' }}>Développement personnel</option>
                        </select>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Les articles "Le pouvoir secret" s'affichent sur la page du même nom</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="author_id" class="form-label">Auteur</label>
                        <select class="form-select @error('author_id') is-invalid @enderror" id="author_id" name="author_id">
                            <option value="">Sélectionner un auteur</option>
                            @foreach($authors as $author)
                                <option value="{{ $author->id }}" {{ old('author_id', auth()->id()) == $author->id ? 'selected' : '' }}>
                                    {{ $author->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('author_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Options</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_premium" name="is_premium" value="1" {{ old('is_premium') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_premium">
                                Article Premium
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.blogs.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Annuler
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Créer l'article
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

