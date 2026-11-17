@extends('admin.layout')

@section('title', 'Détails de l\'Événement')

@section('content')
<div class="content-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-calendar-event"></i> Détails de l'Événement</h1>
    <div>
        <a href="{{ route('admin.events.edit', $event) }}" class="btn btn-warning">
            <i class="bi bi-pencil"></i> Modifier
        </a>
        <a href="{{ route('admin.events.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <table class="table table-bordered">
                    <tr>
                        <th width="200">ID</th>
                        <td>{{ $event->id }}</td>
                    </tr>
                    <tr>
                        <th>Titre</th>
                        <td><strong>{{ $event->title }}</strong></td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td>{{ $event->description }}</td>
                    </tr>
                    <tr>
                        <th>Date</th>
                        <td>{{ $event->event_date ? \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Lieu</th>
                        <td>{{ $event->location ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Durée</th>
                        <td>{{ $event->duration ?: '—' }}</td>
                    </tr>
                    <tr>
                        <th>Contact</th>
                        <td>{{ $event->contact ?: '—' }}</td>
                    </tr>
                    <tr>
                        <th>Prix</th>
                        <td>
                            @if($event->price)
                                {{ number_format($event->price, 2) }} $
                            @else
                                <span class="text-muted">Gratuit</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Participants</th>
                        <td>{{ $event->current_participants ?? 0 }}</td>
                    </tr>
                    <tr>
                        <th>Statut</th>
                        <td>
                            @if($event->status === 'upcoming')
                                <span class="badge bg-primary">À venir</span>
                            @elseif($event->status === 'ongoing')
                                <span class="badge bg-success">En cours</span>
                            @elseif($event->status === 'completed')
                                <span class="badge bg-secondary">Terminé</span>
                            @else
                                <span class="badge bg-danger">Annulé</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Créé le</th>
                        <td>{{ $event->created_at->format('d/m/Y à H:i') }}</td>
                    </tr>
                    <tr>
                        <th>Modifié le</th>
                        <td>{{ $event->updated_at->format('d/m/Y à H:i') }}</td>
                    </tr>
                    <tr>
                        <th>Galerie</th>
                        <td>
                            @if($event->media && $event->media->isNotEmpty())
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($event->media as $media)
                                        <img src="{{ asset('storage/' . $media->file_path) }}" alt="{{ $media->title }}"
                                             class="rounded border" style="width: 100px; height: 75px; object-fit: cover;" title="{{ $media->title }}">
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted">Aucune image</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <i class="bi bi-people"></i> Inscriptions depuis l'app ({{ $event->registrations->count() }})
    </div>
    <div class="card-body">
        @if($event->registrations->isEmpty())
            <p class="text-muted mb-0">Aucune inscription pour le moment.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Nom</th><th>Téléphone</th><th>Inscrit le</th></tr></thead>
                    <tbody>
                        @foreach($event->registrations as $registration)
                            <tr>
                                <td>{{ $registration->first_name }} {{ $registration->last_name }}</td>
                                <td><a href="tel:{{ $registration->phone }}">{{ $registration->phone }}</a></td>
                                <td>{{ $registration->created_at->format('d/m/Y à H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

