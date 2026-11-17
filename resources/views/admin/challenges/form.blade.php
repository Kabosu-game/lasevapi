@extends('admin.layout')

@section('title', $challenge->exists ? 'Modifier un défi' : 'Nouveau défi')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-trophy"></i> {{ $challenge->exists ? 'Modifier « ' . $challenge->title . ' »' : 'Nouveau défi' }}</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ $challenge->exists ? route('admin.challenges.update', $challenge) : route('admin.challenges.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if($challenge->exists) @method('PUT') @endif

            <div class="row">
                <div class="col-md-7">
                    <h5 class="mb-3" style="color: var(--primary-color);">Le défi</h5>
                    <div class="mb-3">
                        <label class="form-label" for="title">Titre <span class="text-danger">*</span></label>
                        <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $challenge->title) }}" placeholder="21 jours de méditation" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="objective">Objectif <span class="text-danger">*</span></label>
                        <input type="text" id="objective" name="objective" class="form-control @error('objective') is-invalid @enderror" value="{{ old('objective', $challenge->objective) }}" placeholder="Méditer quotidiennement" required>
                        @error('objective')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="description">Description / règles</label>
                        <textarea id="description" name="description" rows="4" class="form-control">{{ old('description', $challenge->description) }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="type">Type <span class="text-danger">*</span></label>
                            <select id="type" name="type" class="form-select">
                                @foreach(\App\Models\Challenge::TYPES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type', $challenge->type) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Le type permet à l'app de valider automatiquement la journée quand l'activité correspondante est réalisée.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="image">Image</label>
                            <input type="file" id="image" name="image" accept="image/*" class="form-control @error('image') is-invalid @enderror">
                            @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <h5 class="mb-3" style="color: var(--primary-color);">Programmation</h5>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label" for="starts_at">Début <span class="text-danger">*</span></label>
                            <input type="date" id="starts_at" name="starts_at" class="form-control @error('starts_at') is-invalid @enderror" value="{{ old('starts_at', $challenge->starts_at?->format('Y-m-d')) }}" required>
                            @error('starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label" for="ends_at">Fin <span class="text-danger">*</span></label>
                            <input type="date" id="ends_at" name="ends_at" class="form-control @error('ends_at') is-invalid @enderror" value="{{ old('ends_at', $challenge->ends_at?->format('Y-m-d')) }}" required>
                            @error('ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" @checked(old('is_published', $challenge->is_published))>
                        <label class="form-check-label" for="is_published">Publié (visible dans l'app)</label>
                    </div>
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" id="recurring_monthly" name="recurring_monthly" value="1" @checked(old('recurring_monthly', $challenge->recurring_monthly))>
                        <label class="form-check-label" for="recurring_monthly">Renouvelé automatiquement chaque mois</label>
                    </div>

                    <h5 class="mb-3" style="color: var(--primary-color);">Progression et récompenses</h5>
                    <div class="row">
                        <div class="col-4 mb-3">
                            <label class="form-label" for="target_days">Jours à accomplir</label>
                            <input type="number" id="target_days" name="target_days" min="1" max="31" class="form-control @error('target_days') is-invalid @enderror" value="{{ old('target_days', $challenge->target_days) }}" required>
                            @error('target_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-4 mb-3">
                            <label class="form-label" for="points_per_day">Points / jour</label>
                            <input type="number" id="points_per_day" name="points_per_day" min="0" class="form-control" value="{{ old('points_per_day', $challenge->points_per_day) }}" required>
                        </div>
                        <div class="col-4 mb-3">
                            <label class="form-label" for="completion_bonus">Bonus final</label>
                            <input type="number" id="completion_bonus" name="completion_bonus" min="0" class="form-control" value="{{ old('completion_bonus', $challenge->completion_bonus) }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="badge_name">Badge obtenu à la réussite</label>
                        <input type="text" id="badge_name" name="badge_name" class="form-control" value="{{ old('badge_name', $challenge->badge_name) }}" placeholder="Esprit serein">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="reward">Récompense pour les gagnants</label>
                        <textarea id="reward" name="reward" rows="3" class="form-control" placeholder="Ex. : les 3 premiers gagnent une séance de coaching offerte.">{{ old('reward', $challenge->reward) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Enregistrer</button>
                <a href="{{ $challenge->exists ? route('admin.challenges.show', $challenge) : route('admin.challenges.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
