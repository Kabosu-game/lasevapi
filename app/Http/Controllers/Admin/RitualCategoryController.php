<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RitualCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** ESPACE SACRÉ DES LOIS : catégories de la bibliothèque. */
class RitualCategoryController extends Controller
{
    public function index()
    {
        $categories = RitualCategory::withCount('rituals')->orderBy('sort_order')->orderBy('id')->get();
        return view('admin.ritual-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.ritual-categories.form', ['category' => new RitualCategory([
            'icon' => 'spa', 'is_active' => true, 'sort_order' => (int) RitualCategory::max('sort_order') + 1,
        ])]);
    }

    public function store(Request $request)
    {
        RitualCategory::create($this->validated($request));
        return redirect()->route('admin.ritual-categories.index')->with('success', 'Catégorie créée.');
    }

    public function edit(RitualCategory $ritualCategory)
    {
        return view('admin.ritual-categories.form', ['category' => $ritualCategory]);
    }

    public function update(Request $request, RitualCategory $ritualCategory)
    {
        $ritualCategory->update($this->validated($request, $ritualCategory));
        return redirect()->route('admin.ritual-categories.index')->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(RitualCategory $ritualCategory)
    {
        if ($ritualCategory->rituals()->exists()) {
            return back()->with('error', 'Cette catégorie contient des tutoriels : déplacez-les ou supprimez-les d\'abord, ou désactivez la catégorie.');
        }
        $ritualCategory->delete();
        return redirect()->route('admin.ritual-categories.index')->with('success', 'Catégorie supprimée.');
    }

    private function validated(Request $request, ?RitualCategory $category = null): array
    {
        $request->merge(['slug' => Str::slug($request->input('slug') ?: $request->input('name', ''))]);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:60', Rule::unique('ritual_categories', 'slug')->ignore($category?->id)],
            'description' => 'nullable|string|max:2000',
            'icon' => ['nullable', Rule::in(array_keys(RitualCategory::ICONS))],
            'sort_order' => 'nullable|integer|min:0|max:999',
        ]);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');
        return $data;
    }
}
