<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InnerGate;
use App\Models\InnerGateProgress;
use App\Models\InnerGateStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** FACE À TOI — 7 Portes Intérieures : textes des portes et de leurs étapes. */
class InnerGateAdminController extends Controller
{
    public function index()
    {
        $gates = InnerGate::withCount(['steps', 'completions'])->orderBy('number')->get();
        // Nombre d'utilisateurs ayant commencé chaque porte.
        $started = InnerGateProgress::query()
            ->join('inner_gate_steps', 'inner_gate_steps.id', '=', 'inner_gate_progress.inner_gate_step_id')
            ->groupBy('inner_gate_steps.inner_gate_id')
            ->selectRaw('inner_gate_steps.inner_gate_id as gate_id, COUNT(DISTINCT inner_gate_progress.user_id) as users')
            ->pluck('users', 'gate_id');

        return view('admin.inner-gates.index', compact('gates', 'started'));
    }

    public function show(InnerGate $innerGate)
    {
        $innerGate->load('steps');
        return view('admin.inner-gates.show', ['gate' => $innerGate]);
    }

    public function edit(InnerGate $innerGate)
    {
        return view('admin.inner-gates.form', ['gate' => $innerGate]);
    }

    public function update(Request $request, InnerGate $innerGate)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'theme' => 'required|string|max:255',
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'description' => 'nullable|string|max:5000',
            'affirmation' => 'nullable|string|max:255',
            'companion_message' => 'nullable|string|max:500',
            'image' => 'nullable|image|max:5120',
        ]);
        unset($data['image']);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $this->deleteFile($innerGate->image);
            $data['image'] = $request->file('image')->store('inner-gates', 'public');
        }
        $innerGate->update($data);

        return redirect()->route('admin.inner-gates.show', $innerGate)->with('success', 'Porte mise à jour.');
    }

    // ----- Étapes -----

    public function createStep(InnerGate $innerGate)
    {
        return view('admin.inner-gates.step-form', [
            'gate' => $innerGate,
            'step' => new InnerGateStep(['position' => (int) $innerGate->steps()->max('position') + 1, 'type' => 'lesson']),
        ]);
    }

    public function storeStep(Request $request, InnerGate $innerGate)
    {
        $data = $this->validatedStep($request);
        if ($request->hasFile('media_file')) {
            $data['media_path'] = $request->file('media_file')->store('inner-gates/media', 'public');
        }
        $innerGate->steps()->create($data);

        return redirect()->route('admin.inner-gates.show', $innerGate)->with('success', 'Étape ajoutée.');
    }

    public function editStep(InnerGate $innerGate, InnerGateStep $step)
    {
        abort_unless($step->inner_gate_id === $innerGate->id, 404);
        return view('admin.inner-gates.step-form', ['gate' => $innerGate, 'step' => $step]);
    }

    public function updateStep(Request $request, InnerGate $innerGate, InnerGateStep $step)
    {
        abort_unless($step->inner_gate_id === $innerGate->id, 404);
        $data = $this->validatedStep($request);

        if ($request->boolean('remove_media')) {
            $this->deleteFile($step->media_path);
            $data['media_path'] = null;
        }
        if ($request->hasFile('media_file')) {
            $this->deleteFile($step->media_path);
            $data['media_path'] = $request->file('media_file')->store('inner-gates/media', 'public');
        }
        $step->update($data);

        return redirect()->route('admin.inner-gates.show', $innerGate)->with('success', 'Étape mise à jour.');
    }

    public function destroyStep(InnerGate $innerGate, InnerGateStep $step)
    {
        abort_unless($step->inner_gate_id === $innerGate->id, 404);
        $this->deleteFile($step->media_path);
        $step->delete();

        return back()->with('success', 'Étape supprimée.');
    }

    private function validatedStep(Request $request): array
    {
        $data = $request->validate([
            'position' => 'required|integer|min:1|max:999',
            'type' => ['required', Rule::in(array_keys(InnerGateStep::TYPES))],
            'title' => 'required|string|max:255',
            'content' => 'nullable|string|max:10000',
            'prompts_text' => 'nullable|required_if:type,introspection|string|max:5000',
            'duration_minutes' => 'nullable|integer|min:1|max:240',
            'media_url' => 'nullable|url|max:500',
            'media_file' => 'nullable|file|mimes:mp3,m4a,aac,wav,ogg,mp4,mov,webm,m4v|max:512000',
        ], [
            'prompts_text.required_if' => 'Une étape d\'introspection doit contenir au moins une question.',
        ]);

        $prompts = self::lines($data['prompts_text'] ?? null);
        unset($data['prompts_text'], $data['media_file']);
        $data['prompts'] = $data['type'] === 'introspection' ? $prompts : null;

        return $data;
    }

    /** Une ligne = un élément ; les lignes vides sont ignorées. */
    public static function lines(?string $text): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\R/u', (string) $text) ?: []),
            fn ($line) => $line !== '',
        ));
    }

    private function deleteFile(?string $path): void
    {
        if ($path && !str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }
}
