@extends('admin.layout')

@section('title', $pattern->exists ? 'Modifier un exercice' : 'Nouvel exercice')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-wind"></i> {{ $pattern->exists ? 'Modifier « ' . $pattern->name . ' »' : 'Nouvel exercice de respiration' }}</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ $pattern->exists ? route('admin.breathing-patterns.update', $pattern) : route('admin.breathing-patterns.store') }}" method="POST">
            @csrf
            @if($pattern->exists) @method('PUT') @endif

            <div class="row">
                <div class="col-md-7">
                    <div class="mb-3">
                        <label class="form-label" for="name">Nom <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $pattern->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" rows="3" maxlength="500" class="form-control @error('description') is-invalid @enderror">{{ old('description', $pattern->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-5">
                    @foreach([
                        'inhale_seconds' => ['Inspiration (s)', 1, true],
                        'hold_in_seconds' => ['Rétention poumons pleins (s)', 0, false],
                        'exhale_seconds' => ['Expiration (s)', 1, true],
                        'hold_out_seconds' => ['Pause poumons vides (s)', 0, false],
                    ] as $field => [$label, $min, $required])
                        <div class="mb-3">
                            <label class="form-label" for="{{ $field }}">{{ $label }} @if($required)<span class="text-danger">*</span>@endif</label>
                            <input type="number" id="{{ $field }}" name="{{ $field }}" min="{{ $min }}" max="30" class="form-control @error($field) is-invalid @enderror" value="{{ old($field, $pattern->$field) }}" @required($required)>
                            @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                    <small class="text-muted d-block mb-3">0 = phase absente.</small>
                    <div class="mb-3">
                        <label class="form-label" for="sort_order">Ordre d'affichage</label>
                        <input type="number" id="sort_order" name="sort_order" min="0" max="999" class="form-control" value="{{ old('sort_order', $pattern->sort_order ?? 0) }}">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $pattern->is_active))>
                        <label class="form-check-label" for="is_active">Visible dans l'app</label>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Enregistrer</button>
                <a href="{{ route('admin.breathing-patterns.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
