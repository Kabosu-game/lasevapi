@extends('admin.layout')

@section('title', 'CMS - Gestion de l\'Application')

@section('content')
<div class="content-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-gear-fill"></i> CMS - Gestion de l'Application</h1>
</div>

<!-- Onglets pour les différents groupes -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#general" type="button">
            <i class="bi bi-info-circle"></i> Général
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#appearance" type="button">
            <i class="bi bi-palette"></i> Apparence
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#media" type="button">
            <i class="bi bi-camera-video"></i> Médias
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#content" type="button">
            <i class="bi bi-file-text"></i> Contenu
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- Onglet Général -->
    <div class="tab-pane fade show active" id="general" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Paramètres Généraux
            </div>
            <div class="card-body">
                <form action="{{ route('admin.cms.bulk-update') }}" method="POST">
                    @csrf
                    <div class="row">
                        @foreach($groups['general'] as $setting)
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ $setting->label }}</label>
                                @if($setting->description)
                                    <small class="form-text text-muted d-block mb-2">{{ $setting->description }}</small>
                                @endif
                                @if($setting->type === 'boolean')
                                    <select name="settings[{{ $setting->key }}]" class="form-select">
                                        <option value="1" {{ $setting->value == '1' ? 'selected' : '' }}>Activé</option>
                                        <option value="0" {{ $setting->value == '0' ? 'selected' : '' }}>Désactivé</option>
                                    </select>
                                @else
                                    <input type="text" name="settings[{{ $setting->key }}]" class="form-control" value="{{ $setting->value }}">
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Enregistrer les modifications
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Onglet Apparence -->
    <div class="tab-pane fade" id="appearance" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-palette"></i> Apparence & Thème
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($groups['appearance'] as $setting)
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">{{ $setting->label }}</label>
                            @if($setting->description)
                                <small class="form-text text-muted d-block mb-2">{{ $setting->description }}</small>
                            @endif

                            @if($setting->type === 'image')
                                <!-- Upload d'image -->
                                <div class="mb-2">
                                    @if($setting->value)
                                        <div class="mb-2">
                                            <img src="{{ asset('storage/' . $setting->value) }}" 
                                                 alt="{{ $setting->label }}" 
                                                 class="img-thumbnail" 
                                                 style="max-width: 300px; max-height: 200px;">
                                        </div>
                                        <a href="{{ route('admin.cms.delete-file', $setting->key) }}" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Supprimer cette image ?')">
                                            <i class="bi bi-trash"></i> Supprimer
                                        </a>
                                    @endif
                                </div>
                                <form action="{{ route('admin.cms.update', $setting->key) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    @method('POST')
                                    <input type="file" name="file" class="form-control mb-2" accept="image/*">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="bi bi-upload"></i> Téléverser
                                    </button>
                                </form>
                            @elseif(str_contains($setting->key, 'color'))
                                <!-- Sélecteur de couleur -->
                                <form action="{{ route('admin.cms.update', $setting->key) }}" method="POST" id="color-form-{{ $setting->key }}">
                                    @csrf
                                    @method('POST')
                                    <div class="input-group">
                                        <input type="color" id="color-picker-{{ $setting->key }}" value="{{ $setting->value ?? '#265533' }}" class="form-control form-control-color" onchange="document.getElementById('color-input-{{ $setting->key }}').value = this.value">
                                        <input type="text" id="color-input-{{ $setting->key }}" name="value" value="{{ $setting->value ?? '#265533' }}" class="form-control" onchange="document.getElementById('color-picker-{{ $setting->key }}').value = this.value">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check"></i>
                                        </button>
                                    </div>
                                </form>
                            @else
                                <!-- Champ texte -->
                                <form action="{{ route('admin.cms.update', $setting->key) }}" method="POST">
                                    @csrf
                                    @method('POST')
                                    <div class="input-group">
                                        <input type="text" name="value" value="{{ $setting->value }}" class="form-control">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check"></i>
                                        </button>
                                    </div>
                                </form>
                            @endif
                            <a href="{{ route('admin.cms.reset', $setting->key) }}" 
                               class="btn btn-sm btn-outline-secondary mt-2"
                               onclick="return confirm('Réinitialiser ce paramètre ?')"
                               title="Réinitialiser">
                                <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Onglet Médias -->
    <div class="tab-pane fade" id="media" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-camera-video"></i> Gestion des Médias
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($groups['media'] as $setting)
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $setting->label }}</h5>
                                    @if($setting->description)
                                        <p class="text-muted small">{{ $setting->description }}</p>
                                    @endif

                                    @if($setting->type === 'video')
                                        @if($setting->value)
                                            <div class="mb-2">
                                                <video controls class="w-100" style="max-height: 250px;">
                                                    <source src="{{ asset('storage/' . $setting->value) }}" type="video/mp4">
                                                    Votre navigateur ne supporte pas la lecture de vidéos.
                                                </video>
                                            </div>
                                            <div class="mb-2">
                                                <a href="{{ route('admin.cms.delete-file', $setting->key) }}" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Supprimer cette vidéo ?')">
                                                    <i class="bi bi-trash"></i> Supprimer la vidéo
                                                </a>
                                            </div>
                                        @endif
                                        <form action="{{ route('admin.cms.update', $setting->key) }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            @method('POST')
                                            <div class="mb-2">
                                                <label class="form-label">Téléverser une nouvelle vidéo</label>
                                                <input type="file" name="file" class="form-control" accept="video/*">
                                                <small class="form-text text-muted">Formats acceptés: MP4, WebM, MOV (Max: 20MB)</small>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-upload"></i> Téléverser
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Onglet Contenu -->
    <div class="tab-pane fade" id="content" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-file-text"></i> Contenu Textuel
            </div>
            <div class="card-body">
                <form action="{{ route('admin.cms.bulk-update') }}" method="POST">
                    @csrf
                    <div class="row">
                        @foreach($groups['content'] as $setting)
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ $setting->label }}</label>
                                @if($setting->description)
                                    <small class="form-text text-muted d-block mb-2">{{ $setting->description }}</small>
                                @endif
                                @if(strlen($setting->value) > 100 || str_contains($setting->key, 'message') || str_contains($setting->key, 'description') || str_contains($setting->key, 'about') || str_contains($setting->key, 'policy'))
                                    <textarea name="settings[{{ $setting->key }}]" class="form-control" rows="{{ max(3, min(16, (int) ceil(mb_strlen((string) $setting->value) / 110) + substr_count((string) $setting->value, "\n"))) }}">{{ $setting->value }}</textarea>
                                @else
                                    <input type="text" name="settings[{{ $setting->key }}]" class="form-control" value="{{ $setting->value }}">
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Enregistrer les modifications
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .form-control-color {
        width: 60px;
        height: 38px;
    }
    .img-thumbnail {
        border-radius: 8px;
    }
    .nav-tabs .nav-link {
        color: var(--primary-color);
    }
    .nav-tabs .nav-link.active {
        background-color: var(--primary-color);
        color: white;
    }
</style>
@endpush

