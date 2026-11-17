@extends('admin.layout')

@section('title', $card->exists ? 'Modifier une carte' : 'Nouvelle carte')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-suit-diamond"></i> {{ $card->exists ? 'Modifier « ' . $card->name . ' »' : 'Nouvelle carte oracle' }}</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ $card->exists ? route('admin.oracle-cards.update', $card) : route('admin.oracle-cards.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if($card->exists) @method('PUT') @endif

            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label" for="name">Nom de la carte <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $card->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="message">Message révélé <span class="text-danger">*</span></label>
                        <input type="text" id="message" name="message" class="form-control @error('message') is-invalid @enderror" value="{{ old('message', $card->message) }}" maxlength="500" required>
                        @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="interpretation">Interprétation <span class="text-danger">*</span></label>
                        <textarea id="interpretation" name="interpretation" rows="6" class="form-control @error('interpretation') is-invalid @enderror" required>{{ old('interpretation', $card->interpretation) }}</textarea>
                        @error('interpretation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Formulée comme une interprétation symbolique et une piste de réflexion, jamais comme un événement certain. L'app la personnalise ensuite selon le profil de l'utilisateur.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="keywords">Mots-clés</label>
                        <input type="text" id="keywords" name="keywords" class="form-control" value="{{ old('keywords', $card->keywords) }}" placeholder="renouveau, élan, confiance">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label" for="image">Illustration (optionnelle)</label>
                        @if($card->image_url)
                            <div class="mb-2"><img src="{{ $card->image_url }}" alt="" style="max-width:160px;border-radius:10px;"></div>
                        @endif
                        <input type="file" id="image" name="image" accept="image/*" class="form-control @error('image') is-invalid @enderror">
                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Format portrait conseillé (2:3), 5 Mo max. Sans image, l'app affiche un visuel par défaut.</small>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $card->is_active))>
                        <label class="form-check-label" for="is_active">Carte active (peut être tirée)</label>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Enregistrer</button>
                <a href="{{ route('admin.oracle-cards.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
