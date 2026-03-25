@extends('admin.layout')

@section('title', 'Nouvel Événement')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-calendar-event"></i> Nouvel Événement</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.events.store') }}" method="POST" enctype="multipart/form-data">
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
                          id="description" name="description" rows="5" required>{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="event_date" class="form-label">Date de l'événement <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('event_date') is-invalid @enderror" 
                               id="event_date" name="event_date" value="{{ old('event_date') }}" required>
                        @error('event_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="location" class="form-label">Lieu <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('location') is-invalid @enderror" 
                               id="location" name="location" value="{{ old('location') }}" required>
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
                               id="price" name="price" value="{{ old('price') }}" min="0">
                        @error('price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="current_participants" class="form-label">Participants actuels</label>
                        <input type="number" class="form-control @error('current_participants') is-invalid @enderror" 
                               id="current_participants" name="current_participants" value="{{ old('current_participants', 0) }}" min="0">
                        @error('current_participants')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="status" class="form-label">Statut <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                            <option value="upcoming" {{ old('status') === 'upcoming' ? 'selected' : '' }}>À venir</option>
                            <option value="ongoing" {{ old('status') === 'ongoing' ? 'selected' : '' }}>En cours</option>
                            <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Terminé</option>
                            <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>Annulé</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Galerie d'images</label>
                <input type="file" class="form-control @error('gallery_images.*') is-invalid @enderror"
                       name="gallery_images[]" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" multiple>
                @error('gallery_images.*')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Vous pouvez sélectionner plusieurs images (JPG, PNG, GIF, WebP — max 5 Mo chacune). Elles s'afficheront dans l'app dans la section Galerie de l'événement.</small>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.events.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Annuler
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Créer l'événement
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

