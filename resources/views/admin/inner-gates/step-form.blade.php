@extends('admin.layout')

@section('title', $step->exists ? 'Modifier une étape' : 'Nouvelle étape')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-list-ol"></i> {{ $gate->number }}. {{ $gate->name }} — {{ $step->exists ? 'modifier l\'étape' : 'nouvelle étape' }}</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ $step->exists ? route('admin.inner-gates.steps.update', [$gate, $step]) : route('admin.inner-gates.steps.store', $gate) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if($step->exists) @method('PUT') @endif

            <div class="row">
                <div class="col-md-7">
                    <div class="row">
                        <div class="col-3 mb-3">
                            <label class="form-label" for="position">Ordre <span class="text-danger">*</span></label>
                            <input type="number" id="position" name="position" min="1" class="form-control @error('position') is-invalid @enderror" value="{{ old('position', $step->position) }}" required>
                            @error('position')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-9 mb-3">
                            <label class="form-label" for="type">Type <span class="text-danger">*</span></label>
                            <select id="type" name="type" class="form-select">
                                @foreach(\App\Models\InnerGateStep::TYPES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type', $step->type) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="title">Titre <span class="text-danger">*</span></label>
                        <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $step->title) }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="content">Texte / consignes</label>
                        <textarea id="content" name="content" rows="9" class="form-control @error('content') is-invalid @enderror">{{ old('content', $step->content) }}</textarea>
                        @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Les retours à la ligne sont conservés dans l'app.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="prompts_text">Questions d'introspection (une par ligne)</label>
                        <textarea id="prompts_text" name="prompts_text" rows="4" class="form-control @error('prompts_text') is-invalid @enderror" placeholder="Quelle peur revient le plus souvent dans tes pensées ?">{{ old('prompts_text', implode("\n", $step->prompts ?? [])) }}</textarea>
                        @error('prompts_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Uniquement pour le type « Introspection ». Les réponses restent sur le téléphone de l'utilisateur.</small>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="mb-3">
                        <label class="form-label" for="duration_minutes">Durée (minutes)</label>
                        <input type="number" id="duration_minutes" name="duration_minutes" min="1" max="240" class="form-control @error('duration_minutes') is-invalid @enderror" value="{{ old('duration_minutes', $step->duration_minutes) }}">
                        @error('duration_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Pour une méditation, une respiration ou un exercice, l'app propose un minuteur de cette durée.</small>
                    </div>

                    <h6 class="mt-4" style="color: var(--primary-color);">Audio ou vidéo (facultatif)</h6>
                    @if($step->media)
                        <p class="small mb-1">Actuel : <a href="{{ $step->media }}" target="_blank" rel="noopener">{{ Str::limit($step->media, 50) }}</a></p>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="remove_media" name="remove_media" value="1">
                            <label class="form-check-label" for="remove_media">Retirer le fichier téléversé</label>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label" for="media_file">Téléverser un fichier</label>
                        <input type="file" id="media_file" name="media_file" accept="audio/*,video/*" class="form-control @error('media_file') is-invalid @enderror">
                        @error('media_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">MP3, M4A, WAV, MP4, MOV, WEBM — 500 Mo max. Prioritaire sur le lien.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="media_url">… ou lien direct</label>
                        <input type="url" id="media_url" name="media_url" class="form-control @error('media_url') is-invalid @enderror" value="{{ old('media_url', $step->media_url) }}" placeholder="https://…/meditation.mp3">
                        @error('media_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Enregistrer</button>
                <a href="{{ route('admin.inner-gates.show', $gate) }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
