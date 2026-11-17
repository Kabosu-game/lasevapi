{{-- Catégorie de l'espace ACTIVITÉS dans l'app (liste gérée dans « Activités — catégories ») --}}
@php($options = \App\Models\ActivityCategory::options())
@php($current = old('category', $selected ?? array_key_first($options)))
<div class="mb-3">
    <label for="category" class="form-label">Catégorie (espace Activités) <span class="text-danger">*</span></label>
    <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
        @foreach($options as $value => $label)
            <option value="{{ $value }}" @selected($current === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('category')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <small class="form-text text-muted">
        Les catégories se gèrent dans <a href="{{ route('admin.activity-categories.index') }}">Activités — catégories</a>.
    </small>
</div>
