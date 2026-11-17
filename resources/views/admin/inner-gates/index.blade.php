@extends('admin.layout')

@section('title', 'Face à toi — 7 Portes')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-door-open"></i> Face à toi — Les 7 Portes Intérieures</h1>
    <small class="text-muted">Chaque porte s'ouvre quand la précédente est terminée. Les étapes se font dans l'ordre.</small>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr><th>Porte</th><th>Thème</th><th>Étapes</th><th>Commencée par</th><th>Terminée par</th><th>Statut</th><th>Actions</th></tr>
                </thead>
                <tbody>
                @foreach($gates as $gate)
                    <tr>
                        <td>
                            <span class="d-inline-block rounded-circle me-2 align-middle" style="width:14px;height:14px;background:{{ $gate->color }};"></span>
                            <a href="{{ route('admin.inner-gates.show', $gate) }}"><strong>{{ $gate->number }}. {{ $gate->name }}</strong></a>
                        </td>
                        <td>{{ $gate->theme }}</td>
                        <td>{{ $gate->steps_count }}</td>
                        <td>{{ (int) ($started[$gate->id] ?? 0) }} utilisateur(s)</td>
                        <td>{{ $gate->completions_count }} utilisateur(s)</td>
                        <td>@if($gate->is_active)<span class="badge bg-success">Active</span>@else<span class="badge bg-secondary">Masquée</span>@endif</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.inner-gates.show', $gate) }}" class="btn btn-info" title="Étapes"><i class="bi bi-list-ol"></i></a>
                                <a href="{{ route('admin.inner-gates.edit', $gate) }}" class="btn btn-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
