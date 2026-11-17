@extends('admin.layout')

@section('title', $ritual->exists ? 'Modifier un tutoriel' : 'Nouveau tutoriel')

@php
    $steps = old('steps', $ritual->steps ?? []);
    if (empty($steps)) {
        $steps = [['title' => '', 'text' => '']];
    }
@endphp

@section('content')
<div class="content-header">
    <h1><i class="bi bi-flower1"></i> {{ $ritual->exists ? 'Modifier « ' . $ritual->title . ' »' : 'Nouveau tutoriel' }}</h1>
    <small class="text-muted">Un tutoriel publié doit présenter ses objectifs, le matériel, les étapes, les précautions et les traditions auxquelles il se rattache.</small>
</div>

@if($errors->any())
    <div class="alert alert-danger">Le tutoriel n'a pas été enregistré : vérifiez les champs signalés en rouge.</div>
@endif

<form action="{{ $ritual->exists ? route('admin.rituals.update', $ritual) : route('admin.rituals.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if($ritual->exists) @method('PUT') @endif

    <div class="card mb-3">
        <div class="card-header">Présentation</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-7">
                    <div class="mb-3">
                        <label class="form-label" for="title">Titre <span class="text-danger">*</span></label>
                        <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $ritual->title) }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="summary">Résumé</label>
                        <textarea id="summary" name="summary" rows="3" class="form-control @error('summary') is-invalid @enderror">{{ old('summary', $ritual->summary) }}</textarea>
                        @error('summary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="mb-3">
                        <label class="form-label" for="ritual_category_id">Catégorie <span class="text-danger">*</span></label>
                        <select id="ritual_category_id" name="ritual_category_id" class="form-select @error('ritual_category_id') is-invalid @enderror" required>
                            <option value="">Choisir…</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((int) old('ritual_category_id', $ritual->ritual_category_id) === $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('ritual_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label" for="duration_minutes">Durée (min)</label>
                            <input type="number" id="duration_minutes" name="duration_minutes" min="1" class="form-control @error('duration_minutes') is-invalid @enderror" value="{{ old('duration_minutes', $ritual->duration_minutes) }}">
                            @error('duration_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label" for="sort_order">Ordre</label>
                            <input type="number" id="sort_order" name="sort_order" min="0" class="form-control" value="{{ old('sort_order', $ritual->sort_order ?? 0) }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="image">Image</label>
                        @if($ritual->image_url)<div class="mb-2"><img src="{{ $ritual->image_url }}" alt="" style="max-width:180px;border-radius:8px;"></div>@endif
                        <input type="file" id="image" name="image" accept="image/*" class="form-control @error('image') is-invalid @enderror">
                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="video_url">Vidéo (lien, facultatif)</label>
                        <input type="url" id="video_url" name="video_url" class="form-control @error('video_url') is-invalid @enderror" value="{{ old('video_url', $ritual->video_url) }}" placeholder="https://…/tutoriel.mp4">
                        @error('video_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Contenu du tutoriel</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="objectives_text">Objectifs (un par ligne)</label>
                    <textarea id="objectives_text" name="objectives_text" rows="4" class="form-control @error('objectives_text') is-invalid @enderror">{{ old('objectives_text', implode("\n", $ritual->objectives ?? [])) }}</textarea>
                    @error('objectives_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="materials_text">Matériel (un élément par ligne)</label>
                    <textarea id="materials_text" name="materials_text" rows="4" class="form-control @error('materials_text') is-invalid @enderror">{{ old('materials_text', implode("\n", $ritual->materials ?? [])) }}</textarea>
                    @error('materials_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <label class="form-label">Étapes</label>
            @error('steps')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
            <div id="steps">
                @foreach($steps as $i => $step)
                    <div class="step-row border rounded p-3 mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong class="step-number">Étape {{ $loop->iteration }}</strong>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-step" title="Retirer"><i class="bi bi-x-lg"></i></button>
                        </div>
                        <input type="text" name="steps[{{ $i }}][title]" class="form-control mb-2" placeholder="Titre de l'étape" value="{{ $step['title'] ?? '' }}">
                        <textarea name="steps[{{ $i }}][text]" rows="3" class="form-control" placeholder="Déroulement">{{ $step['text'] ?? '' }}</textarea>
                    </div>
                @endforeach
            </div>
            <button type="button" id="add-step" class="btn btn-outline-primary btn-sm mb-3"><i class="bi bi-plus"></i> Ajouter une étape</button>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="precautions_text">Précautions (une par ligne)</label>
                    <textarea id="precautions_text" name="precautions_text" rows="4" class="form-control @error('precautions_text') is-invalid @enderror" placeholder="Ne jamais laisser une bougie allumée sans surveillance.">{{ old('precautions_text', implode("\n", $ritual->precautions ?? [])) }}</textarea>
                    @error('precautions_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="traditions">Traditions auxquelles il se rattache</label>
                    <textarea id="traditions" name="traditions" rows="4" class="form-control @error('traditions') is-invalid @enderror">{{ old('traditions', $ritual->traditions) }}</textarea>
                    @error('traditions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" @checked(old('is_published', $ritual->is_published))>
                <label class="form-check-label" for="is_published">Publié (visible dans l'app)</label>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Enregistrer</button>
        <a href="{{ route('admin.rituals.index') }}" class="btn btn-secondary">Annuler</a>
    </div>
</form>

<template id="step-template">
    <div class="step-row border rounded p-3 mb-2">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong class="step-number"></strong>
            <button type="button" class="btn btn-sm btn-outline-danger remove-step" title="Retirer"><i class="bi bi-x-lg"></i></button>
        </div>
        <input type="text" data-name="title" class="form-control mb-2" placeholder="Titre de l'étape">
        <textarea data-name="text" rows="3" class="form-control" placeholder="Déroulement"></textarea>
    </div>
</template>
@endsection

@push('scripts')
<script>
    (function () {
        const container = document.getElementById('steps');
        const renumber = () => container.querySelectorAll('.step-number').forEach((el, i) => { el.textContent = 'Étape ' + (i + 1); });

        document.getElementById('add-step').addEventListener('click', () => {
            const index = 'n' + Date.now();
            const row = document.getElementById('step-template').content.firstElementChild.cloneNode(true);
            row.querySelectorAll('[data-name]').forEach((field) => {
                field.name = 'steps[' + index + '][' + field.dataset.name + ']';
            });
            container.appendChild(row);
            renumber();
            row.querySelector('input').focus();
        });

        container.addEventListener('click', (event) => {
            const button = event.target.closest('.remove-step');
            if (!button) return;
            button.closest('.step-row').remove();
            renumber();
        });
    })();
</script>
@endpush
