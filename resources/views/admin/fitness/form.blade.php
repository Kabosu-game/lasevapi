@extends('admin.layout')

@section('title', $program->exists ? 'Modifier un programme' : 'Nouveau programme')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-activity"></i> {{ $program->exists ? 'Modifier « ' . $program->title . ' »' : 'Nouveau programme' }}</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ $program->exists ? route('admin.fitness-programs.update', $program) : route('admin.fitness-programs.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if($program->exists) @method('PUT') @endif

            <div class="row">
                <div class="col-md-7">
                    <div class="mb-3">
                        <label class="form-label" for="title">Titre <span class="text-danger">*</span></label>
                        <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $program->title) }}" placeholder="Yoga débutant — 7 jours" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" rows="5" class="form-control">{{ old('description', $program->description) }}</textarea>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="mb-3">
                        <label class="form-label" for="category">Catégorie</label>
                        <select id="category" name="category" class="form-select">
                            @foreach(\App\Models\FitnessProgram::CATEGORIES as $value => $label)
                                <option value="{{ $value }}" @selected(old('category', $program->category) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label" for="level">Niveau</label>
                            <select id="level" name="level" class="form-select">
                                @foreach(\App\Models\FitnessProgram::LEVELS as $value => $label)
                                    <option value="{{ $value }}" @selected(old('level', $program->level) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label" for="duration_days">Durée</label>
                            <select id="duration_days" name="duration_days" class="form-select">
                                @foreach(\App\Models\FitnessProgram::DURATIONS as $days)
                                    <option value="{{ $days }}" @selected((int) old('duration_days', $program->duration_days) === $days)>{{ $days }} jours</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="image">Image</label>
                        @if($program->image_url)<div class="mb-2"><img src="{{ $program->image_url }}" alt="" style="max-width:200px;border-radius:8px;"></div>@endif
                        <input type="file" id="image" name="image" accept="image/*" class="form-control @error('image') is-invalid @enderror">
                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="sort_order">Ordre d'affichage</label>
                        <input type="number" id="sort_order" name="sort_order" min="0" class="form-control" value="{{ old('sort_order', $program->sort_order ?? 0) }}">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" @checked(old('is_published', $program->is_published))>
                        <label class="form-check-label" for="is_published">Publié (visible dans l'app)</label>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Enregistrer</button>
                <a href="{{ $program->exists ? route('admin.fitness-programs.show', $program) : route('admin.fitness-programs.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
