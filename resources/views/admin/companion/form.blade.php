@extends('admin.layout')

@php
    $isNotification = $message->kind !== 'card';
    $triggers = \App\Models\CompanionMessage::triggersFor($message->kind);
    $selectedDays = $message->weekdays ? str_split($message->weekdays) : array_keys(\App\Models\CompanionMessage::WEEKDAYS);
@endphp

@section('title', $message->exists ? 'Modifier un message' : ($isNotification ? 'Nouvelle notification' : 'Nouveau message'))

@section('content')
<div class="content-header">
    <h1>
        <i class="bi {{ $isNotification ? 'bi-phone-vibrate' : 'bi-chat-heart' }}"></i>
        {{ $message->exists ? 'Modifier' : 'Nouveau' }} · {{ $isNotification ? 'notification sur le téléphone' : 'message dans « Mon compagnon »' }}
    </h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ $message->exists ? route('admin.companion.update', $message) : route('admin.companion.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if($message->exists) @method('PUT') @endif
            <input type="hidden" name="kind" value="{{ $message->kind }}">

            <h5 class="mb-3">Quand ?</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="trigger">Condition <span class="text-danger">*</span></label>
                    <select id="trigger" name="trigger" class="form-select @error('trigger') is-invalid @enderror" required>
                        @foreach($triggers as $key => [$label, $valueLabel])
                            <option value="{{ $key }}" data-value-label="{{ $valueLabel }}" @selected(old('trigger', $message->trigger) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('trigger')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3" id="activity-field">
                    <label class="form-label" for="activity">Activité</label>
                    <select id="activity" name="activity" class="form-select @error('activity') is-invalid @enderror">
                        <option value="">—</option>
                        @foreach(\App\Models\CompanionMessage::ACTIVITIES as $key => $label)
                            <option value="{{ $key }}" @selected(old('activity', $message->activity) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('activity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3" id="value-field">
                    <label class="form-label" for="trigger_value" id="value-label">Nombre de jours</label>
                    <input type="number" id="trigger_value" name="trigger_value" min="1" max="365" class="form-control @error('trigger_value') is-invalid @enderror" value="{{ old('trigger_value', $message->trigger_value) }}">
                    @error('trigger_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            @if($isNotification)
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="send_time">Heure d'envoi <span class="text-danger">*</span></label>
                        <input type="time" id="send_time" name="send_time" class="form-control @error('send_time') is-invalid @enderror" value="{{ old('send_time', $message->send_time) }}" required>
                        @error('send_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Matin, après-midi ou soir.</small>
                    </div>
                    <div class="col-md-9 mb-3">
                        <label class="form-label d-block">Jours</label>
                        @foreach(\App\Models\CompanionMessage::WEEKDAYS as $key => $label)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="weekdays[]" id="day{{ $key }}" value="{{ $key }}" @checked(in_array((string) $key, old('weekdays', $selectedDays), true))>
                                <label class="form-check-label" for="day{{ $key }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <h5 class="mb-3 mt-2">Quoi ?</h5>
            <div class="mb-3">
                <label class="form-label" for="title">Titre @if($isNotification)<span class="text-danger">*</span>@endif</label>
                <input type="text" id="title" name="title" maxlength="120" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $message->title) }}" @if($isNotification) required @endif>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="body">Texte <span class="text-danger">*</span></label>
                <textarea id="body" name="body" rows="3" maxlength="500" class="form-control @error('body') is-invalid @enderror" required>{{ old('body', $message->body) }}</textarea>
                @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted"><code>{prenom}</code> : prénom · <code>{jours}</code> : nombre de jours · <code>{defi}</code> : nom du défi</small>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="action">Écran ouvert {{ $isNotification ? 'en touchant la notification' : 'par le bouton' }}</label>
                    <select id="action" name="action" class="form-select">
                        <option value="">— Aucun —</option>
                        @foreach(\App\Models\CompanionMessage::ACTIONS as $key => $label)
                            <option value="{{ $key }}" @selected(old('action', $message->action) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @unless($isNotification)
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="action_label">Texte du bouton</label>
                        <input type="text" id="action_label" name="action_label" maxlength="60" class="form-control" value="{{ old('action_label', $message->action_label) }}" placeholder="Ex. Respirer">
                    </div>
                @endunless
            </div>

            @unless($isNotification)
                <div class="mb-3">
                    <label class="form-label" for="audio">Audio (facultatif)</label>
                    <input type="file" id="audio" name="audio" accept="audio/*" class="form-control @error('audio') is-invalid @enderror">
                    @error('audio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @if($message->audio)
                        <div class="mt-2">
                            <audio controls src="{{ $message->audioUrl() }}"></audio>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remove_audio" value="1" id="remove_audio">
                                <label class="form-check-label" for="remove_audio">Retirer l'audio</label>
                            </div>
                        </div>
                    @endif
                </div>
            @endunless

            <h5 class="mb-3 mt-2">Réglages</h5>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label" for="priority">Priorité (0 à 100)</label>
                    <input type="number" id="priority" name="priority" min="0" max="100" class="form-control" value="{{ old('priority', $message->priority) }}">
                    <small class="text-muted">Le plus haut passe en premier.</small>
                </div>
                @unless($isNotification)
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="cooldown_hours">Délai avant de le remontrer (heures)</label>
                        <input type="number" id="cooldown_hours" name="cooldown_hours" min="0" max="720" class="form-control" value="{{ old('cooldown_hours', $message->cooldown_hours) }}">
                    </div>
                @endunless
                <div class="col-md-3 mb-3">
                    <label class="form-label" for="starts_at">À partir du</label>
                    <input type="date" id="starts_at" name="starts_at" class="form-control" value="{{ old('starts_at', $message->starts_at?->toDateString()) }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label" for="ends_at">Jusqu'au</label>
                    <input type="date" id="ends_at" name="ends_at" class="form-control @error('ends_at') is-invalid @enderror" value="{{ old('ends_at', $message->ends_at?->toDateString()) }}">
                    @error('ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="form-check form-switch mb-3">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $message->is_active))>
                <label class="form-check-label" for="is_active">Actif</label>
            </div>

            <div class="mt-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Enregistrer</button>
                <a href="{{ route('admin.companion.index', ['kind' => $message->kind]) }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const trigger = document.getElementById('trigger');
        const activity = document.getElementById('activity-field');
        const value = document.getElementById('value-field');
        const valueLabel = document.getElementById('value-label');
        function refresh() {
            const option = trigger.options[trigger.selectedIndex];
            activity.style.display = trigger.value === 'activity_not_done' ? '' : 'none';
            const label = option.dataset.valueLabel;
            value.style.display = label ? '' : 'none';
            if (label) valueLabel.textContent = label;
        }
        trigger.addEventListener('change', refresh);
        refresh();
    })();
</script>
@endsection
