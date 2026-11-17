@extends('admin.layout')

@section('title', $gate->name)

@section('content')
<div class="content-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>
            <span class="d-inline-block rounded-circle me-1 align-middle" style="width:18px;height:18px;background:{{ $gate->color }};"></span>
            {{ $gate->number }}. {{ $gate->name }}
        </h1>
        <small class="text-muted">{{ $gate->theme }} — {{ $gate->is_active ? 'Active' : 'Masquée' }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.inner-gates.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Les 7 portes</a>
        <a href="{{ route('admin.inner-gates.edit', $gate) }}" class="btn btn-warning"><i class="bi bi-pencil"></i> Modifier la porte</a>
        <a href="{{ route('admin.inner-gates.steps.create', $gate) }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Ajouter une étape</a>
    </div>
</div>

@if($gate->description || $gate->affirmation)
<div class="card mb-3">
    <div class="card-body">
        @if($gate->description)<p class="mb-2">{{ $gate->description }}</p>@endif
        @if($gate->affirmation)<p class="mb-0 fst-italic">« {{ $gate->affirmation }} »</p>@endif
    </div>
</div>
@endif

<div class="card">
    <div class="card-header">Étapes (dans l'ordre où l'utilisateur les réalise)</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Ordre</th><th>Type</th><th>Étape</th><th>Durée</th><th>Média</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($gate->steps as $step)
                    <tr>
                        <td><span class="badge bg-success">{{ $step->position }}</span></td>
                        <td>{{ \App\Models\InnerGateStep::TYPES[$step->type] ?? $step->type }}</td>
                        <td>
                            <strong>{{ $step->title }}</strong><br>
                            <small class="text-muted">{{ Str::limit($step->content, 90) }}</small>
                            @if($step->type === 'introspection')
                                <br><small class="text-muted">{{ count($step->prompts ?? []) }} question(s)</small>
                            @endif
                        </td>
                        <td>{{ $step->duration_minutes ? $step->duration_minutes . ' min' : '—' }}</td>
                        <td>
                            @if($step->media)
                                <a href="{{ $step->media }}" target="_blank" rel="noopener"><i class="bi bi-{{ $step->media_kind === 'audio' ? 'music-note-beamed' : 'play-circle' }}"></i> {{ $step->media_kind === 'audio' ? 'Audio' : 'Vidéo' }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.inner-gates.steps.edit', [$gate, $step]) }}" class="btn btn-warning"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('admin.inner-gates.steps.destroy', [$gate, $step]) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette étape ? La progression des utilisateurs sur cette étape sera effacée.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">Aucune étape : cette porte apparaît « bientôt disponible » dans l'app et ne bloque pas la suivante.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
