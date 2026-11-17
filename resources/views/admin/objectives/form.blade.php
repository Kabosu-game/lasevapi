@extends('admin.layout')

@section('title', $objective->exists ? 'Modifier un objectif' : 'Nouvel objectif')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-bullseye"></i> {{ $objective->exists ? 'Modifier « ' . $objective->name . ' »' : 'Nouvel objectif' }}</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ $objective->exists ? route('admin.objectives.update', $objective) : route('admin.objectives.store') }}" method="POST">
            @csrf
            @if($objective->exists) @method('PUT') @endif

            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label" for="name">Objectif <span class="text-danger">*</span></label>
                    <input type="text" id="name" name="name" maxlength="120" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $objective->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="sort_order">Ordre d'affichage</label>
                    <input type="number" id="sort_order" name="sort_order" min="0" max="999" class="form-control" value="{{ old('sort_order', $objective->sort_order ?? 0) }}">
                </div>
            </div>

            <div class="mt-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Enregistrer</button>
                <a href="{{ route('admin.objectives.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
