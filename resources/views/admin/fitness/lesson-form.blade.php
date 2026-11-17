@extends('admin.layout')

@section('title', $lesson->exists ? 'Modifier une leçon' : 'Nouvelle leçon')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-collection-play"></i> {{ $program->title }} — {{ $lesson->exists ? 'modifier la leçon' : 'nouvelle leçon' }}</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ $lesson->exists ? route('admin.fitness-programs.lessons.update', [$program, $lesson]) : route('admin.fitness-programs.lessons.store', $program) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if($lesson->exists) @method('PUT') @endif

            <div class="row">
                <div class="col-md-7">
                    <div class="row">
                        <div class="col-4 mb-3">
                            <label class="form-label" for="day_number">Jour <span class="text-danger">*</span></label>
                            <input type="number" id="day_number" name="day_number" min="1" max="{{ $program->duration_days }}" class="form-control @error('day_number') is-invalid @enderror" value="{{ old('day_number', $lesson->day_number) }}" required>
                            @error('day_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-8 mb-3">
                            <label class="form-label" for="title">Titre <span class="text-danger">*</span></label>
                            <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $lesson->title) }}" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="description">Description / consignes</label>
                        <textarea id="description" name="description" rows="5" class="form-control">{{ old('description', $lesson->description) }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label" for="duration_minutes">Durée (minutes)</label>
                            <input type="number" id="duration_minutes" name="duration_minutes" min="1" class="form-control" value="{{ old('duration_minutes', $lesson->duration_minutes) }}">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label" for="sort_order">Ordre dans la journée</label>
                            <input type="number" id="sort_order" name="sort_order" min="0" class="form-control" value="{{ old('sort_order', $lesson->sort_order ?? 0) }}">
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <h6 style="color: var(--primary-color);">Vidéo de la leçon</h6>
                    @if($lesson->video)
                        <p class="small">Actuelle : <a href="{{ $lesson->video }}" target="_blank" rel="noopener">{{ Str::limit($lesson->video, 50) }}</a></p>
                    @endif
                    <div class="mb-3">
                        <label class="form-label" for="video_file">Téléverser un fichier</label>
                        <input type="file" id="video_file" name="video_file" accept="video/*" class="form-control @error('video_file') is-invalid @enderror">
                        @error('video_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">MP4, MOV, WEBM — 500 Mo max. Prioritaire sur le lien.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="video_url">… ou lien direct vers la vidéo</label>
                        <input type="url" id="video_url" name="video_url" class="form-control @error('video_url') is-invalid @enderror" value="{{ old('video_url', $lesson->video_url) }}" placeholder="https://…/lecon.mp4">
                        @error('video_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Enregistrer</button>
                <a href="{{ route('admin.fitness-programs.show', $program) }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
