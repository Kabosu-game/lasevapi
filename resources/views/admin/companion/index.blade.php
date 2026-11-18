@extends('admin.layout')

@section('title', 'Compagnon et rappels')

@section('content')
<div class="content-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h1><i class="bi bi-bell"></i> Compagnon et rappels</h1>
    <a href="{{ route('admin.companion.create', ['kind' => $kind]) }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> {{ $kind === 'card' ? 'Nouveau message' : 'Nouvelle notification' }}
    </a>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ $kind === 'notification' ? 'active' : '' }}" href="{{ route('admin.companion.index') }}">
            <i class="bi bi-phone-vibrate"></i> Notifications sur le téléphone
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $kind === 'card' ? 'active' : '' }}" href="{{ route('admin.companion.index', ['kind' => 'card']) }}">
            <i class="bi bi-chat-heart"></i> Messages dans « Mon compagnon »
        </a>
    </li>
</ul>

@if($kind === 'notification')
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        Chaque notification part à l'heure indiquée si sa condition est remplie ce jour-là. Exemple : « Méditation non faite » à 19:30
        ne part pas si l'utilisateur a déjà médité. Les changements s'appliquent la prochaine fois que l'utilisateur ouvre l'app.
        Dans le texte, <code>{prenom}</code> est remplacé par le prénom, <code>{jours}</code> par le nombre de jours (série, défi)
        et <code>{defi}</code> par le nom du défi.
    </div>

    <form action="{{ route('admin.companion.settings') }}" method="POST" class="card mb-3">
        @csrf
        <div class="card-body d-flex align-items-end gap-3 flex-wrap">
            <div>
                <label class="form-label mb-1" for="max_per_day">Maximum de notifications par jour et par utilisateur</label>
                <input type="number" id="max_per_day" name="max_per_day" min="1" max="10" value="{{ $maxPerDay }}" class="form-control" style="width: 120px">
            </div>
            <button type="submit" class="btn btn-outline-primary">Enregistrer</button>
            <small class="text-muted">Au-delà, seules les notifications les plus prioritaires de la journée partent.</small>
        </div>
    </form>
@else
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        Message affiché en haut de « Mon compagnon » à l'ouverture. Le plus prioritaire dont la condition est remplie s'affiche ;
        le délai évite de remontrer le même message trop souvent. <code>{prenom}</code> est remplacé par le prénom.
    </div>
@endif

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        @if($kind === 'notification')<th>Heure</th>@endif
                        <th>Condition</th>
                        <th>Message</th>
                        <th>Priorité</th>
                        <th>État</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($messages as $message)
                    <tr class="{{ $message->is_active ? '' : 'table-secondary' }}">
                        @if($kind === 'notification')
                            <td>
                                <strong>{{ $message->send_time }}</strong><br>
                                <small class="text-muted">{{ $message->weekdaysLabel() }}</small>
                            </td>
                        @endif
                        <td>{{ $message->triggerLabel() }}</td>
                        <td style="max-width: 420px">
                            @if($message->title)<strong>{{ $message->title }}</strong><br>@endif
                            <span class="text-muted">{{ \Illuminate\Support\Str::limit($message->body, 140) }}</span>
                            @if($message->audio)<br><small><i class="bi bi-music-note"></i> audio</small>@endif
                        </td>
                        <td>{{ $message->priority }}</td>
                        <td>
                            @if($message->is_active)
                                <span class="badge bg-success">Actif</span>
                            @else
                                <span class="badge bg-secondary">Désactivé</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <form action="{{ route('admin.companion.toggle', $message) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary" title="{{ $message->is_active ? 'Désactiver' : 'Activer' }}">
                                        <i class="bi {{ $message->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                    </button>
                                </form>
                                <a href="{{ route('admin.companion.edit', $message) }}" class="btn btn-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('admin.companion.destroy', $message) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce message ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">Aucun message pour l'instant.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
