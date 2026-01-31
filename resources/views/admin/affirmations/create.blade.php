@extends('admin.layout')

@section('title', 'Nouvelle Affirmation')

@section('content')
<div class="content-header">
    <h1><i class="bi bi-quote"></i> Nouvelle Affirmation</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.affirmations.store') }}" method="POST">
            @csrf
            
            <div class="mb-3">
                <label for="category_id" class="form-label">Catégorie <span class="text-danger">*</span></label>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required style="max-width: 300px;">
                        <option value="">Sélectionner une catégorie</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="btn-group">
                        <a href="{{ route('admin.affirmation-categories.index') }}" class="btn btn-outline-primary btn-sm" target="_blank">
                            <i class="bi bi-tags"></i> Gérer les catégories
                        </a>
                        <button type="button" class="btn btn-success btn-sm" id="openAddCategoryModal">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" id="deleteSelectedCategory">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                @error('category_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="title" class="form-label">Titre <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('title') is-invalid @enderror" 
                       id="title" name="title" value="{{ old('title') }}" required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="body" class="form-label">Contenu <span class="text-danger">*</span></label>
                <textarea class="form-control @error('body') is-invalid @enderror" 
                          id="body" name="body" rows="6" required>{{ old('body') }}</textarea>
                @error('body')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.affirmations.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Annuler
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Créer l'affirmation
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal pour ajouter une catégorie rapidement -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addCategoryForm">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter une catégorie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_category_name" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" id="new_category_name" name="name" class="form-control" required>
                        <div class="invalid-feedback" id="newCategoryError"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const addBtn = document.getElementById('openAddCategoryModal');
    const addModalEl = document.getElementById('addCategoryModal');
    const addCategoryForm = document.getElementById('addCategoryForm');
    const deleteBtn = document.getElementById('deleteSelectedCategory');
    const categorySelect = document.getElementById('category_id');
    const newCategoryError = document.getElementById('newCategoryError');
    const bsModal = addModalEl ? new bootstrap.Modal(addModalEl) : null;

    if (addBtn && bsModal) {
        addBtn.addEventListener('click', () => bsModal.show());
    }

    if (addCategoryForm) {
        addCategoryForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            newCategoryError.textContent = '';
            const name = document.getElementById('new_category_name').value.trim();
            if (!name) {
                newCategoryError.textContent = 'Le nom est requis.';
                document.getElementById('new_category_name').classList.add('is-invalid');
                return;
            }
            document.getElementById('new_category_name').classList.remove('is-invalid');

            const url = '{{ route('admin.affirmation-categories.store') }}';
            const token = '{{ csrf_token() }}';
            const formData = new FormData();
            formData.append('name', name);
            try {
                const resp = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                    body: formData
                });
                const data = await resp.json();
                if (!resp.ok) {
                    const msg = data.errors?.name ? data.errors.name[0] : (data.error || 'Erreur');
                    newCategoryError.textContent = msg;
                    document.getElementById('new_category_name').classList.add('is-invalid');
                    return;
                }
                // Ajouter l'option et la sélectionner
                const option = document.createElement('option');
                option.value = data.category.id;
                option.textContent = data.category.name;
                option.selected = true;
                categorySelect.appendChild(option);
                // Mettre à jour la valeur du select
                categorySelect.value = data.category.id;
                bsModal.hide();
            } catch (err) {
                newCategoryError.textContent = 'Erreur réseau';
            }
        });
    }

    if (deleteBtn) {
        deleteBtn.addEventListener('click', async function () {
            const selectedId = categorySelect.value;
            if (!selectedId) {
                alert('Sélectionnez d'abord une catégorie à supprimer.');
                return;
            }
            if (!confirm('Confirmer la suppression de la catégorie sélectionnée ?')) return;
            const url = '/admin/affirmation-categories/' + selectedId;
            const token = '{{ csrf_token() }}';
            try {
                const resp = await fetch(url, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
                });
                const data = await resp.json();
                if (!resp.ok || !data.success) {
                    alert(data.error || 'Impossible de supprimer la catégorie');
                    return;
                }
                // Retirer l'option du select
                const opt = categorySelect.querySelector('option[value="' + selectedId + '"]');
                if (opt) opt.remove();
                categorySelect.value = '';
            } catch (err) {
                alert('Erreur réseau');
            }
        });
    }
});
</script>
@endsection
@endsection

