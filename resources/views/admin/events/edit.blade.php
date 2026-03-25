@extends('admin.layout')

@section('title', 'Modifier l\'Événement')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-calendar-event"></i> Modifier l'Événement</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.events.update', $event) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="mb-3">
                <label for="title" class="form-label">Titre <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('title') is-invalid @enderror" 
                       id="title" name="title" value="{{ old('title', $event->title) }}" required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                <textarea class="form-control @error('description') is-invalid @enderror" 
                          id="description" name="description" rows="5" required>{{ old('description', $event->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="event_date" class="form-label">Date de l'événement <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('event_date') is-invalid @enderror" 
                               id="event_date" name="event_date" value="{{ old('event_date', $event->event_date ? \Carbon\Carbon::parse($event->event_date)->format('Y-m-d') : '') }}" required>
                        @error('event_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="location" class="form-label">Lieu <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('location') is-invalid @enderror" 
                               id="location" name="location" value="{{ old('location', $event->location) }}" required>
                        @error('location')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="price" class="form-label">Prix ($)</label>
                        <input type="number" step="0.01" class="form-control @error('price') is-invalid @enderror" 
                               id="price" name="price" value="{{ old('price', $event->price) }}" min="0">
                        @error('price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="current_participants" class="form-label">Participants actuels</label>
                        <input type="number" class="form-control @error('current_participants') is-invalid @enderror" 
                               id="current_participants" name="current_participants" value="{{ old('current_participants', $event->current_participants ?? 0) }}" min="0">
                        @error('current_participants')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="status" class="form-label">Statut <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                            <option value="upcoming" {{ old('status', $event->status) === 'upcoming' ? 'selected' : '' }}>À venir</option>
                            <option value="ongoing" {{ old('status', $event->status) === 'ongoing' ? 'selected' : '' }}>En cours</option>
                            <option value="completed" {{ old('status', $event->status) === 'completed' ? 'selected' : '' }}>Terminé</option>
                            <option value="cancelled" {{ old('status', $event->status) === 'cancelled' ? 'selected' : '' }}>Annulé</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Galerie d'images</label>
                @if($event->media->isNotEmpty())
                    <div class="row g-2 mb-3">
                        @foreach($event->media as $media)
                            <div class="col-auto">
                                <div class="position-relative d-inline-block">
                                    <img src="{{ asset('storage/' . $media->file_path) }}" alt="{{ $media->title }}"
                                         class="rounded border" style="width: 120px; height: 90px; object-fit: cover;">
                                    <label class="position-absolute bottom-0 start-0 m-1 bg-dark bg-opacity-75 text-white px-2 py-1 rounded small text-truncate" style="max-width: 110px;" title="{{ $media->title }}">{{ $media->title }}</label>
                                    <label class="position-absolute top-0 end-0 m-1">
                                        <input type="checkbox" name="delete_media[]" value="{{ $media->id }}" class="form-check-input"> Supprimer
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                <input type="file" class="form-control @error('gallery_images.*') is-invalid @enderror"
                       name="gallery_images[]" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" multiple>
                @error('gallery_images.*')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Ajouter de nouvelles images (JPG, PNG, GIF, WebP — max 5 Mo). Cochez « Supprimer » sur une image pour la retirer.</small>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.events.index') }}" class="btn btn-secondary">
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

