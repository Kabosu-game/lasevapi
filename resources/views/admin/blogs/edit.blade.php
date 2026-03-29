@extends('admin.layout')

@section('title', 'Modifier l\'Article de Blog')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-journal-text"></i> Modifier l'Article de Blog</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.blogs.update', $blog) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="mb-3">
                <label for="title" class="form-label">Titre <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('title') is-invalid @enderror" 
                       id="title" name="title" value="{{ old('title', $blog->title) }}" required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                <textarea class="form-control @error('description') is-invalid @enderror" 
                          id="description" name="description" rows="3" required>{{ old('description', $blog->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="body" class="form-label">Contenu <span class="text-danger">*</span></label>
                <textarea class="form-control @error('body') is-invalid @enderror" 
                          id="body" name="body" rows="10" required>{{ old('body', $blog->body) }}</textarea>
                @error('body')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="cover_image" class="form-label">Photo de couverture</label>
                @if($blog->cover_image)
                    <div class="mb-2">
                        <img src="{{ asset($blog->cover_image) }}" alt="Couverture actuelle" class="rounded border" style="max-height: 180px; object-fit: cover;">
                    </div>
                @endif
                <input class="form-control @error('cover_image') is-invalid @enderror"
                       type="file"
                       id="cover_image"
                       name="cover_image"
                       accept="image/*">
                <small class="form-text text-muted">Optionnel. Laisser vide pour conserver la couverture actuelle. Affichée dans le catalogue de l’app.</small>
                @error('cover_image')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="images" class="form-label">Ajouter des photos (galerie)</label>
                <input class="form-control @error('images') is-invalid @enderror"
                       type="file"
                       id="images"
                       name="images[]"
                       multiple
                       accept="image/*">
                <small class="form-text text-muted">Optionnel. Jusqu'à 10 images supplémentaires.</small>
            </div>

            <div class="mb-3">
                <label for="videos" class="form-label">Ajouter des vidéos (sous la galerie dans l’app)</label>
                @if($blog->media && $blog->media->where('media_type', 'video')->isNotEmpty())
                    <div class="mb-2 small text-muted">Vidéos déjà jointes : {{ $blog->media->where('media_type', 'video')->count() }} fichier(s)</div>
                @endif
                <input class="form-control @error('videos') is-invalid @enderror @error('videos.*') is-invalid @enderror"
                       type="file"
                       id="videos"
                       name="videos[]"
                       multiple
                       accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov,.m4v">
                <small class="form-text text-muted">Optionnel. Jusqu’à 5 nouveaux fichiers par enregistrement (MP4, WebM, MOV).</small>
                @error('videos')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @error('videos.*')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="category" class="form-label">Catégorie</label>
                        <select class="form-select @error('category') is-invalid @enderror" id="category" name="category">
                            <option value="general" {{ old('category', $blog->category ?? 'general') == 'general' ? 'selected' : '' }}>Général</option>
                            <option value="pouvoir-secret" {{ old('category', $blog->category) == 'pouvoir-secret' ? 'selected' : '' }}>Le pouvoir secret</option>
                            <option value="meditation" {{ old('category', $blog->category) == 'meditation' ? 'selected' : '' }}>Méditation</option>
                            <option value="bien-etre" {{ old('category', $blog->category) == 'bien-etre' ? 'selected' : '' }}>Bien-être</option>
                            <option value="developpement" {{ old('category', $blog->category) == 'developpement' ? 'selected' : '' }}>Développement personnel</option>
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
                                <option value="{{ $author->id }}" {{ old('author_id', $blog->author_id) == $author->id ? 'selected' : '' }}>
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
                            <input class="form-check-input" type="checkbox" id="is_premium" name="is_premium" value="1" {{ old('is_premium', $blog->is_premium) ? 'checked' : '' }}>
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
                    <i class="bi bi-check-circle"></i> Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

