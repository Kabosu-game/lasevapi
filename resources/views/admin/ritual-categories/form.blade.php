@extends('admin.layout')

@section('title', $category->exists ? 'Modifier une catégorie' : 'Nouvelle catégorie')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-flower1"></i> {{ $category->exists ? 'Modifier « ' . $category->name . ' »' : 'Nouvelle catégorie' }}</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ $category->exists ? route('admin.ritual-categories.update', $category) : route('admin.ritual-categories.store') }}" method="POST">
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
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" rows="3" class="form-control">{{ old('description', $category->description) }}</textarea>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="mb-3">
                        <label class="form-label" for="slug">Identifiant (facultatif)</label>
                        <input type="text" id="slug" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $category->slug) }}" placeholder="généré à partir du nom">
                        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="icon">Icône dans l'app</label>
                        <select id="icon" name="icon" class="form-select">
                            @foreach(\App\Models\RitualCategory::ICONS as $value => $label)
                                <option value="{{ $value }}" @selected(old('icon', $category->icon) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="sort_order">Ordre d'affichage</label>
                        <input type="number" id="sort_order" name="sort_order" min="0" class="form-control" value="{{ old('sort_order', $category->sort_order ?? 0) }}">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $category->is_active))>
                        <label class="form-check-label" for="is_active">Active (visible dans l'app)</label>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Enregistrer</button>
                <a href="{{ route('admin.ritual-categories.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
