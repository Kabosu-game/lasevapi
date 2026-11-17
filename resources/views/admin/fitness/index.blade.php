@extends('admin.layout')

@section('title', 'Fitness Center')

@section('content')
<div class="content-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-activity"></i> Fitness Center — programmes</h1>
    <a href="{{ route('admin.fitness-programs.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nouveau programme</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr><th>Programme</th><th>Catégorie</th><th>Niveau</th><th>Durée</th><th>Leçons</th><th>Statut</th><th>Actions</th></tr>
                </thead>
                <tbody>
                @forelse($programs as $program)
                    <tr>
                        <td><a href="{{ route('admin.fitness-programs.show', $program) }}"><strong>{{ $program->title }}</strong></a></td>
                        <td>{{ \App\Models\FitnessProgram::CATEGORIES[$program->category] ?? $program->category }}</td>
                        <td>{{ \App\Models\FitnessProgram::LEVELS[$program->level] ?? $program->level }}</td>
                        <td>{{ $program->duration_days }} jours</td>
                        <td>{{ $program->lessons_count }}</td>
                        <td>@if($program->is_published)<span class="badge bg-success">Publié</span>@else<span class="badge bg-secondary">Brouillon</span>@endif</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.fitness-programs.show', $program) }}" class="btn btn-info" title="Leçons"><i class="bi bi-collection-play"></i></a>
                                <a href="{{ route('admin.fitness-programs.edit', $program) }}" class="btn btn-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('admin.fitness-programs.destroy', $program) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce programme et toutes ses leçons ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">Aucun programme. Créez par exemple « Yoga débutant — 7 jours ».</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">{{ $programs->links() }}</div>
    </div>
</div>
@endsection
