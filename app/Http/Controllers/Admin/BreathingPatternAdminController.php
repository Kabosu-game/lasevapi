<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BreathingPattern;
use Illuminate\Http\Request;

/** Exercices de respiration guidés de l'app (catégorie Respiration de l'espace Activités). */
class BreathingPatternAdminController extends Controller
{
    public function index()
    {
        $patterns = BreathingPattern::ordered()->get();
        return view('admin.breathing-patterns.index', compact('patterns'));
    }

    public function create()
    {
        return view('admin.breathing-patterns.form', ['pattern' => new BreathingPattern([
            'inhale_seconds' => 4,
            'hold_in_seconds' => 0,
            'exhale_seconds' => 6,
            'hold_out_seconds' => 0,
            'is_active' => true,
            'sort_order' => (int) BreathingPattern::max('sort_order') + 1,
        ])]);
    }

    public function store(Request $request)
    {
        BreathingPattern::create($this->validated($request));
        return redirect()->route('admin.breathing-patterns.index')->with('success', 'Exercice créé.');
    }

    public function edit(BreathingPattern $breathingPattern)
    {
        return view('admin.breathing-patterns.form', ['pattern' => $breathingPattern]);
    }

    public function update(Request $request, BreathingPattern $breathingPattern)
    {
        $breathingPattern->update($this->validated($request));
        return redirect()->route('admin.breathing-patterns.index')->with('success', 'Exercice mis à jour.');
    }

    public function destroy(BreathingPattern $breathingPattern)
    {
        $breathingPattern->delete();
        return redirect()->route('admin.breathing-patterns.index')->with('success', 'Exercice supprimé.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'inhale_seconds' => 'required|integer|min:1|max:30',
            'hold_in_seconds' => 'nullable|integer|min:0|max:30',
            'exhale_seconds' => 'required|integer|min:1|max:30',
            'hold_out_seconds' => 'nullable|integer|min:0|max:30',
            'sort_order' => 'nullable|integer|min:0|max:999',
        ]);
        $data['hold_in_seconds'] = $data['hold_in_seconds'] ?? 0;
        $data['hold_out_seconds'] = $data['hold_out_seconds'] ?? 0;
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');
        return $data;
    }
}
