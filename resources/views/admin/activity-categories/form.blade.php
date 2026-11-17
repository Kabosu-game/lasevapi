@extends('admin.layout')

@section('title', $category->exists ? 'Modifier une catégorie' : 'Nouvelle catégorie')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-headphones"></i> {{ $category->exists ? 'Modifier « ' . $category->name . ' »' : 'Nouvelle catégorie d\'activités' }}</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ $category->exists ? route('admin.activity-categories.update', $category) : route('admin.activity-categories.store') }}" method="POST">
            @csrf
            @if($category->exists) @method('PUT') @endif

            <div class="row">
                <div class="col-md-7">
                    <div class="mb-3">
                        <label class="form-label" for="name">Nom <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $category->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="description">Texte de présentation</label>
                        <textarea id="description" name="description" rows="3" maxlength="500" class="form-control @error('description') is-invalid @enderror">{{ old('description', $category->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Affiché sur la carte de la catégorie et en haut de sa liste de contenus.</small>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="with_breathing_exercises" name="with_breathing_exercises" value="1" @checked(old('with_breathing_exercises', $category->with_breathing_exercises))>
                        <label class="form-check-label" for="with_breathing_exercises">
                            Proposer les <a href="{{ route('admin.breathing-patterns.index') }}">exercices de respiration guidés</a> dans cette catégorie
                        </label>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="mb-3">
                        <label class="form-label" for="key">Clé</label>
                        @if($category->exists)
                            <input type="text" id="key" class="form-control" value="{{ $category->key }}" disabled>
                            <small class="text-muted">Fixe : elle relie les contenus à la catégorie.</small>
                        @else
                            <input type="text" id="key" name="key" class="form-control @error('key') is-invalid @enderror" value="{{ old('key') }}" placeholder="générée à partir du nom" maxlength="32">
                            @error('key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="icon">Icône dans l'app</label>
                        <select id="icon" name="icon" class="form-select @error('icon') is-invalid @enderror">
                            @foreach(\App\Models\ActivityCategory::ICONS as $value => $label)
                                <option value="{{ $value }}" @selected(old('icon', $category->icon) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('icon')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="color">Couleur de la carte</label>
                        <input type="color" id="color" name="color" class="form-control form-control-color @error('color') is-invalid @enderror" value="{{ old('color', $category->color) }}">
                        @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="sort_order">Ordre d'affichage</label>
                        <input type="number" id="sort_order" name="sort_order" min="0" max="999" class="form-control" value="{{ old('sort_order', $category->sort_order ?? 0) }}">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $category->is_active))>
                        <label class="form-check-label" for="is_active">Visible dans l'app</label>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Enregistrer</button>
                <a href="{{ route('admin.activity-categories.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
