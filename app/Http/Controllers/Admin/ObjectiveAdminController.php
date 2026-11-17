<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Objective;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Objectifs proposés lors de l'inscription dans l'app (Paix intérieure, Meilleur sommeil…). */
class ObjectiveAdminController extends Controller
{
    public function index()
    {
        $objectives = Objective::orderBy('sort_order')->orderBy('id')->get();
        $usage = DB::table('user_objectives')->select('objective_id', DB::raw('count(*) as total'))
            ->groupBy('objective_id')->pluck('total', 'objective_id');
        return view('admin.objectives.index', compact('objectives', 'usage'));
    }

    public function create()
    {
        return view('admin.objectives.form', ['objective' => new Objective([
            'sort_order' => (int) Objective::max('sort_order') + 1,
        ])]);
    }

    public function store(Request $request)
    {
        Objective::create($this->validated($request));
        return redirect()->route('admin.objectives.index')->with('success', 'Objectif créé.');
    }

    public function edit(Objective $objective)
    {
        return view('admin.objectives.form', compact('objective'));
    }

    public function update(Request $request, Objective $objective)
    {
        $objective->update($this->validated($request, $objective));
        return redirect()->route('admin.objectives.index')->with('success', 'Objectif mis à jour.');
    }

    public function destroy(Objective $objective)
    {
        $objective->delete();
        return redirect()->route('admin.objectives.index')->with('success', 'Objectif supprimé.');
    }

    private function validated(Request $request, ?Objective $objective = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('objectives', 'name')->ignore($objective?->id)],
            'sort_order' => 'nullable|integer|min:0|max:999',
        ]);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        return $data;
    }
}
