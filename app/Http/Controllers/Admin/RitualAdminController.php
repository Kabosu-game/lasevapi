<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ritual;
use App\Models\RitualCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/** ESPACE SACRÉ DES LOIS : tutoriels (objectifs, matériel, étapes, précautions, traditions). */
class RitualAdminController extends Controller
{
    public function index(Request $request)
    {
        $categories = RitualCategory::orderBy('sort_order')->orderBy('id')->get();
        $rituals = Ritual::with('category')
            ->when($request->filled('category'), fn ($q) => $q->where('ritual_category_id', $request->integer('category')))
            ->orderBy('ritual_category_id')->orderBy('sort_order')->orderByDesc('id')
            ->paginate(30)->withQueryString();

        return view('admin.rituals.index', compact('rituals', 'categories'));
    }

    public function create(Request $request)
    {
        return view('admin.rituals.form', [
            'ritual' => new Ritual(['ritual_category_id' => $request->integer('category') ?: null, 'is_published' => false]),
            'categories' => RitualCategory::orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('rituals', 'public');
        }
        Ritual::create($data);
        return redirect()->route('admin.rituals.index')->with('success', 'Tutoriel enregistré.');
    }

    public function edit(Ritual $ritual)
    {
        return view('admin.rituals.form', [
            'ritual' => $ritual,
            'categories' => RitualCategory::orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Ritual $ritual)
    {
        $data = $this->validated($request);
        if ($request->hasFile('image')) {
            $this->deleteFile($ritual->image);
            $data['image'] = $request->file('image')->store('rituals', 'public');
        }
        $ritual->update($data);
        return redirect()->route('admin.rituals.index')->with('success', 'Tutoriel mis à jour.');
    }

    public function destroy(Ritual $ritual)
    {
        $this->deleteFile($ritual->image);
        $ritual->delete();
        return back()->with('success', 'Tutoriel supprimé.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'ritual_category_id' => 'required|integer|exists:ritual_categories,id',
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string|max:2000',
            'objectives_text' => 'nullable|string|max:5000',
            'materials_text' => 'nullable|string|max:5000',
            'precautions_text' => 'nullable|string|max:5000',
            'steps' => 'nullable|array|max:50',
            'steps.*.title' => 'nullable|string|max:255',
            'steps.*.text' => 'nullable|string|max:5000',
            'traditions' => 'nullable|string|max:5000',
            'duration_minutes' => 'nullable|integer|min:1|max:600',
            'video_url' => 'nullable|url|max:500',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'image' => 'nullable|image|max:5120',
        ]);

        $ritual = [
            'ritual_category_id' => $data['ritual_category_id'],
            'title' => $data['title'],
            'summary' => $data['summary'] ?? null,
            'objectives' => InnerGateAdminController::lines($data['objectives_text'] ?? null),
            'materials' => InnerGateAdminController::lines($data['materials_text'] ?? null),
            'precautions' => InnerGateAdminController::lines($data['precautions_text'] ?? null),
            'steps' => collect($data['steps'] ?? [])
                ->map(fn ($s) => ['title' => trim($s['title'] ?? ''), 'text' => trim($s['text'] ?? '')])
                ->filter(fn ($s) => $s['title'] !== '' || $s['text'] !== '')
                ->values()->all(),
            'traditions' => trim($data['traditions'] ?? '') ?: null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'video_url' => $data['video_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ];

        // Un tutoriel publié présente obligatoirement les cinq rubriques.
        if ($ritual['is_published']) {
            $missing = [];
            foreach ([
                'objectives_text' => [$ritual['objectives'], 'au moins un objectif'],
                'materials_text' => [$ritual['materials'], 'le matériel'],
                'steps' => [$ritual['steps'], 'au moins une étape'],
                'precautions_text' => [$ritual['precautions'], 'au moins une précaution'],
                'traditions' => [$ritual['traditions'], 'les traditions auxquelles il se rattache'],
            ] as $field => [$value, $label]) {
                if (empty($value)) {
                    $missing[$field] = 'Pour publier, indiquez ' . $label . '.';
                }
            }
            if ($missing) {
                throw ValidationException::withMessages($missing);
            }
        }

        return $ritual;
    }

    private function deleteFile(?string $path): void
    {
        if ($path && !str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }
}
