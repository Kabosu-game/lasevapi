@extends('admin.layout')

@section('title', 'Créer une Méditation')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-plus-circle"></i> Créer une nouvelle méditation</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.meditations.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3" style="color: var(--primary-color);">Informations générales</h5>
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Titre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" 
                               id="title" name="title" value="{{ old('title') }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug</label>
                        <input type="text" class="form-control @error('slug') is-invalid @enderror" 
                               id="slug" name="slug" value="{{ old('slug') }}" 
                               placeholder="auto-généré si vide">
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Laissez vide pour génération automatique</small>
                    </div>

                    @include('admin.meditations._category')

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="4">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="duration" class="form-label">Durée (en secondes)</label>
                        <input type="number" class="form-control @error('duration') is-invalid @enderror" 
                               id="duration" name="duration" value="{{ old('duration') }}" 
                               min="0" placeholder="Ex: 900 (15 minutes)">
                        @error('duration')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <h5 class="mb-3" style="color: var(--primary-color);">Fichier média (Audio/Video)</h5>
                    
                    <div class="mb-3">
                        <label for="media_type" class="form-label">Type de média <span class="text-danger">*</span></label>
                        <select class="form-select @error('media_type') is-invalid @enderror" 
                                id="media_type" name="media_type" required>
                            <option value="">Sélectionner un type</option>
                            <option value="audio" {{ old('media_type') == 'audio' ? 'selected' : '' }}>Audio (MP3, WAV, M4A)</option>
                            <option value="video" {{ old('media_type') == 'video' ? 'selected' : '' }}>Vidéo (MP4, MOV, AVI)</option>
                        </select>
                        @error('media_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="media_file" class="form-label">Fichier média <span class="text-danger">*</span></label>
                        <input type="file" class="form-control @error('media_file') is-invalid @enderror" 
                               id="media_file" name="media_file" accept="audio/*,video/*" required>
                        @error('media_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            Formats acceptés : MP3, MP4, M4A, MOV, AVI, WAV (max 100MB)
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="media_title" class="form-label">Titre du média <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('media_title') is-invalid @enderror" 
                               id="media_title" name="media_title" value="{{ old('media_title') }}" required>
                        @error('media_title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="media_slug" class="form-label">Slug du média</label>
                        <input type="text" class="form-control @error('media_slug') is-invalid @enderror" 
                               id="media_slug" name="media_slug" value="{{ old('media_slug') }}">
                        @error('media_slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="media_duration" class="form-label">Durée du média (en secondes)</label>
                        <input type="number" class="form-control @error('media_duration') is-invalid @enderror" 
                               id="media_duration" name="media_duration" value="{{ old('media_duration') }}" 
                               min="0" placeholder="Ex: 600 (10 minutes)">
                        @error('media_duration')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Créer la méditation
                </button>
                <a href="{{ route('admin.meditations.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Annuler
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

