<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FitnessLesson;
use App\Models\FitnessProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** FITNESS CENTER : programmes et leçons vidéo. */
class FitnessProgramController extends Controller
{
    public function index()
    {
        $programs = FitnessProgram::withCount('lessons')->orderBy('category')->orderBy('sort_order')->paginate(30);
        return view('admin.fitness.index', compact('programs'));
    }

    public function create()
    {
        return view('admin.fitness.form', ['program' => new FitnessProgram([
            'category' => 'yoga', 'level' => 'debutant', 'duration_days' => 7, 'is_published' => true,
        ])]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedProgram($request);
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('fitness', 'public');
        }
        $program = FitnessProgram::create($data);
        return redirect()->route('admin.fitness-programs.show', $program)->with('success', 'Programme créé. Ajoutez maintenant ses leçons.');
    }

    public function show(FitnessProgram $fitnessProgram)
    {
        $fitnessProgram->load('lessons');
        return view('admin.fitness.show', ['program' => $fitnessProgram]);
    }

    public function edit(FitnessProgram $fitnessProgram)
    {
        return view('admin.fitness.form', ['program' => $fitnessProgram]);
    }

    public function update(Request $request, FitnessProgram $fitnessProgram)
    {
        $data = $this->validatedProgram($request);
        if ($request->hasFile('image')) {
            $this->deleteFile($fitnessProgram->image);
            $data['image'] = $request->file('image')->store('fitness', 'public');
        }
        $fitnessProgram->update($data);
        return redirect()->route('admin.fitness-programs.show', $fitnessProgram)->with('success', 'Programme mis à jour.');
    }

    public function destroy(FitnessProgram $fitnessProgram)
    {
        foreach ($fitnessProgram->lessons as $lesson) {
            $this->deleteFile($lesson->video_path);
        }
        $this->deleteFile($fitnessProgram->image);
        $fitnessProgram->delete();
        return redirect()->route('admin.fitness-programs.index')->with('success', 'Programme supprimé.');
    }

    // ----- Leçons -----

    public function createLesson(FitnessProgram $fitnessProgram)
    {
        $next = (int) $fitnessProgram->lessons()->max('day_number') + 1;
        return view('admin.fitness.lesson-form', [
            'program' => $fitnessProgram,
            'lesson' => new FitnessLesson(['day_number' => min($next, $fitnessProgram->duration_days)]),
        ]);
    }

    public function storeLesson(Request $request, FitnessProgram $fitnessProgram)
    {
        $data = $this->validatedLesson($request, $fitnessProgram);
        if ($request->hasFile('video_file')) {
            $data['video_path'] = $request->file('video_file')->store('fitness/videos', 'public');
        }
        $fitnessProgram->lessons()->create($data);
        return redirect()->route('admin.fitness-programs.show', $fitnessProgram)->with('success', 'Leçon ajoutée.');
    }

    public function editLesson(FitnessProgram $fitnessProgram, FitnessLesson $lesson)
    {
        abort_unless($lesson->fitness_program_id === $fitnessProgram->id, 404);
        return view('admin.fitness.lesson-form', ['program' => $fitnessProgram, 'lesson' => $lesson]);
    }

    public function updateLesson(Request $request, FitnessProgram $fitnessProgram, FitnessLesson $lesson)
    {
        abort_unless($lesson->fitness_program_id === $fitnessProgram->id, 404);
        $data = $this->validatedLesson($request, $fitnessProgram);
        if ($request->hasFile('video_file')) {
            $this->deleteFile($lesson->video_path);
            $data['video_path'] = $request->file('video_file')->store('fitness/videos', 'public');
        }
        $lesson->update($data);
        return redirect()->route('admin.fitness-programs.show', $fitnessProgram)->with('success', 'Leçon mise à jour.');
    }

    public function destroyLesson(FitnessProgram $fitnessProgram, FitnessLesson $lesson)
    {
        abort_unless($lesson->fitness_program_id === $fitnessProgram->id, 404);
        $this->deleteFile($lesson->video_path);
        $lesson->delete();
        return back()->with('success', 'Leçon supprimée.');
    }

    private function validatedProgram(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'category' => ['required', Rule::in(array_keys(FitnessProgram::CATEGORIES))],
            'level' => ['required', Rule::in(array_keys(FitnessProgram::LEVELS))],
            'duration_days' => ['required', 'integer', Rule::in(FitnessProgram::DURATIONS)],
            'sort_order' => 'nullable|integer|min:0',
            'image' => 'nullable|image|max:5120',
        ]);
        unset($data['image']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_published'] = $request->boolean('is_published');
        return $data;
    }

    private function validatedLesson(Request $request, FitnessProgram $program): array
    {
        $data = $request->validate([
            'day_number' => 'required|integer|min:1|max:' . $program->duration_days,
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'video_url' => 'nullable|url|max:500',
            'video_file' => 'nullable|file|mimes:mp4,mov,webm,m4v|max:512000',
            'duration_minutes' => 'nullable|integer|min:1|max:600',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        unset($data['video_file']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        return $data;
    }

    private function deleteFile(?string $path): void
    {
        if ($path && !str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }
}
