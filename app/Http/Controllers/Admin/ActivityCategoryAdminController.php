<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** ACTIVITÉS : catégories affichées dans l'app (Méditation, Jogging, Sommeil…). */
class ActivityCategoryAdminController extends Controller
{
    public function index()
    {
        $categories = ActivityCategory::withCount('meditations')->ordered()->get();
        return view('admin.activity-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.activity-categories.form', ['category' => new ActivityCategory([
            'icon' => 'headphones',
            'color' => '#2E7D6B',
            'is_active' => true,
            'sort_order' => (int) ActivityCategory::max('sort_order') + 1,
        ])]);
    }

    public function store(Request $request)
    {
        ActivityCategory::create($this->validated($request));
        return redirect()->route('admin.activity-categories.index')->with('success', 'Catégorie créée.');
    }

    public function edit(ActivityCategory $activityCategory)
    {
        return view('admin.activity-categories.form', ['category' => $activityCategory]);
    }

    public function update(Request $request, ActivityCategory $activityCategory)
    {
        $activityCategory->update($this->validated($request, $activityCategory));
        return redirect()->route('admin.activity-categories.index')->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(ActivityCategory $activityCategory)
    {
        if ($activityCategory->meditations()->exists()) {
            return back()->with('error', 'Cette catégorie contient des contenus : déplacez-les dans une autre catégorie ou masquez la catégorie.');
        }
        $activityCategory->delete();
        return redirect()->route('admin.activity-categories.index')->with('success', 'Catégorie supprimée.');
    }

    private function validated(Request $request, ?ActivityCategory $category = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'icon' => ['required', Rule::in(array_keys(ActivityCategory::ICONS))],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => 'nullable|integer|min:0|max:999',
        ];
        // La clé relie les contenus à la catégorie : elle est fixée à la création.
        if (!$category) {
            $request->merge(['key' => Str::slug($request->input('key') ?: $request->input('name', ''))]);
            $rules['key'] = ['required', 'string', 'max:32', 'alpha_dash', Rule::unique('activity_categories', 'key')];
        }
        $data = $request->validate($rules, ['color.regex' => 'La couleur doit être au format #RRGGBB.']);
        $data['color'] = strtoupper($data['color']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');
        $data['with_breathing_exercises'] = $request->boolean('with_breathing_exercises');
        return $data;
    }
}
