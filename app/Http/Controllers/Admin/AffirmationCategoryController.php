<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffirmationCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AffirmationCategoryController extends Controller
{
    public function index()
    {
        $categories = AffirmationCategory::withCount('affirmations')->orderBy('order')->orderBy('name')->paginate(20);
        return view('admin.affirmation-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.affirmation-categories.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:affirmation_categories,name',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:50',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $category = AffirmationCategory::create([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color,
            'order' => $request->order ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'category' => $category]);
        }
        return redirect()->route('admin.affirmation-categories.index')
            ->with('success', 'Catégorie créée avec succès.');
    }

    public function edit($id)
    {
        $category = AffirmationCategory::findOrFail($id);
        return view('admin.affirmation-categories.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $category = AffirmationCategory::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:affirmation_categories,name,' . $id,
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:50',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $category->update([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color,
            'order' => $request->order ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);
        return redirect()->route('admin.affirmation-categories.index')
            ->with('success', 'Catégorie mise à jour.');
    }

    public function destroy($id)
    {
        $category = AffirmationCategory::findOrFail($id);
        if ($category->affirmations()->count() > 0) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'error' => 'Impossible de supprimer : des affirmations utilisent cette catégorie. Réassignez-les d\'abord.'], 400);
            }
            return redirect()->route('admin.affirmation-categories.index')
                ->with('error', 'Impossible de supprimer : des affirmations utilisent cette catégorie. Réassignez-les d\'abord.');
        }
        $category->delete();
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('admin.affirmation-categories.index')
            ->with('success', 'Catégorie supprimée.');
    }
}
