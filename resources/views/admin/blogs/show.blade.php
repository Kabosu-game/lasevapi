@extends('admin.layout')

@section('title', 'Détails de l\'Article de Blog')

@section('content')
<div class="content-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-journal-text"></i> Détails de l'Article de Blog</h1>
    <div>
        <a href="{{ route('admin.blogs.edit', $blog) }}" class="btn btn-warning">
            <i class="bi bi-pencil"></i> Modifier
        </a>
        <a href="{{ route('admin.blogs.index') }}" class="btn btn-secondary">
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
                        <td>{{ $blog->id }}</td>
                    </tr>
                    <tr>
                        <th>Titre</th>
                        <td><strong>{{ $blog->title }}</strong></td>
                    </tr>
                    <tr>
                        <th>Slug</th>
                        <td><code>{{ $blog->slug ?? 'N/A' }}</code></td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td>{{ $blog->description }}</td>
                    </tr>
                    <tr>
                        <th>Contenu</th>
                        <td>{{ $blog->body }}</td>
                    </tr>
                    <tr>
                        <th>Auteur</th>
                        <td>
                            @if($blog->author)
                                {{ $blog->author->name }}
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Type</th>
                        <td>
                            @if($blog->is_premium)
                                <span class="badge bg-warning">Premium</span>
                            @else
                                <span class="badge bg-success">Gratuit</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Créé le</th>
                        <td>{{ $blog->created_at->format('d/m/Y à H:i') }}</td>
                    </tr>
                    <tr>
                        <th>Modifié le</th>
                        <td>{{ $blog->updated_at->format('d/m/Y à H:i') }}</td>
                    </tr>
                </table>

                @if($blog->media && $blog->media->isNotEmpty())
                    <h4 class="mt-4">Photos</h4>
                    <div class="row g-2 mt-1">
                        @foreach($blog->media as $media)
                            <div class="col-md-3">
                                <img
                                    src="{{ asset($media->file_path) }}"
                                    class="img-thumbnail"
                                    style="max-height: 160px; object-fit: cover;"
                                    alt="Photo du blog"
                                >
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

