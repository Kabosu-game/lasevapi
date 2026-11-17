@extends('admin.layout')

@section('title', $program->title)

@section('content')
<div class="content-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="bi bi-activity"></i> {{ $program->title }}</h1>
        <small class="text-muted">
            {{ \App\Models\FitnessProgram::CATEGORIES[$program->category] ?? $program->category }} —
            {{ \App\Models\FitnessProgram::LEVELS[$program->level] ?? $program->level }} —
            {{ $program->duration_days }} jours —
            {{ $program->is_published ? 'Publié' : 'Brouillon' }}
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.fitness-programs.edit', $program) }}" class="btn btn-warning"><i class="bi bi-pencil"></i> Modifier</a>
        <a href="{{ route('admin.fitness-programs.lessons.create', $program) }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Ajouter une leçon</a>
    </div>
</div>

<div class="card">
    <div class="card-header">Leçons par jour</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Jour</th><th>Leçon</th><th>Vidéo</th><th>Durée</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($program->lessons as $lesson)
                    <tr>
                        <td><span class="badge bg-success">Jour {{ $lesson->day_number }}</span></td>
                        <td><strong>{{ $lesson->title }}</strong><br><small class="text-muted">{{ Str::limit($lesson->description, 80) }}</small></td>
                        <td>
                            @if($lesson->video)
                                <a href="{{ $lesson->video }}" target="_blank" rel="noopener"><i class="bi bi-play-circle"></i> {{ $lesson->video_path ? 'Fichier' : 'Lien' }}</a>
                            @else
                                <span class="text-danger">Aucune</span>
                            @endif
                        </td>
                        <td>{{ $lesson->duration_minutes ? $lesson->duration_minutes . ' min' : '—' }}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.fitness-programs.lessons.edit', [$program, $lesson]) }}" class="btn btn-warning"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('admin.fitness-programs.lessons.destroy', [$program, $lesson]) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette leçon ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">Aucune leçon. Ajoutez la leçon du jour 1.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
