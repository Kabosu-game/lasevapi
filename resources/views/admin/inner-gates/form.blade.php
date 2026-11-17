@extends('admin.layout')

@section('title', 'Modifier une porte')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-door-open"></i> Porte {{ $gate->number }} — modifier</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.inner-gates.update', $gate) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-7">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="name">Nom <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $gate->name) }}" placeholder="Chakra Racine" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="theme">Thème <span class="text-danger">*</span></label>
                            <input type="text" id="theme" name="theme" class="form-control @error('theme') is-invalid @enderror" value="{{ old('theme', $gate->theme) }}" placeholder="Mes fondations et mes peurs" required>
                            @error('theme')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="description">Présentation de la porte</label>
                        <textarea id="description" name="description" rows="5" class="form-control">{{ old('description', $gate->description) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="affirmation">Affirmation</label>
                        <input type="text" id="affirmation" name="affirmation" class="form-control" value="{{ old('affirmation', $gate->affirmation) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="companion_message">Message du compagnon</label>
                        <textarea id="companion_message" name="companion_message" rows="2" maxlength="500" class="form-control">{{ old('companion_message', $gate->companion_message) }}</textarea>
                        <small class="text-muted">Affiché dans le bandeau « Ton compagnon LASÈV te guide » tant que cette porte est en cours.</small>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="mb-3">
                        <label class="form-label" for="color">Couleur</label>
                        <input type="color" id="color" name="color" class="form-control form-control-color @error('color') is-invalid @enderror" value="{{ old('color', $gate->color) }}">
                        @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="image">Image (facultative)</label>
                        @if($gate->image_url)<div class="mb-2"><img src="{{ $gate->image_url }}" alt="" style="max-width:160px;border-radius:8px;"></div>@endif
                        <input type="file" id="image" name="image" accept="image/*" class="form-control @error('image') is-invalid @enderror">
                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Sans image, l'app affiche le symbole du chakra dans la couleur choisie.</small>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $gate->is_active))>
                        <label class="form-check-label" for="is_active">Active (visible dans l'app)</label>
                    </div>
                    <small class="text-muted d-block mt-1">Une porte masquée est ignorée : la suivante s'ouvre directement.</small>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Enregistrer</button>
                <a href="{{ route('admin.inner-gates.show', $gate) }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
