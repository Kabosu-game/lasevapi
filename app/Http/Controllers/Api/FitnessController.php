<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FitnessLesson;
use App\Models\FitnessProgram;
use App\Models\FitnessSession;
use Illuminate\Http\Request;

class FitnessController extends Controller
{
    /** GET /fitness/programs?category=yoga&level=debutant&duration=21 */
    public function programs(Request $request)
    {
        $query = FitnessProgram::where('is_published', true)->withCount('lessons');
        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }
        if ($request->filled('level')) {
            $query->where('level', $request->query('level'));
        }
        if ($request->filled('duration')) {
            $query->where('duration_days', (int) $request->query('duration'));
        }
        $programs = $query->orderBy('sort_order')->orderBy('id')->get();

        // Leçons déjà réalisées par l'utilisateur, par programme.
        $done = $this->completedLessonIds($request);
        $byProgram = FitnessLesson::whereIn('id', $done)->pluck('fitness_program_id')->countBy();
        $programs->each(fn ($p) => $p->setAttribute('completed_lessons', (int) ($byProgram[$p->id] ?? 0)));

        return response()->json(['success' => true, 'data' => $programs]);
    }

    /** GET /fitness/programs/{program} : leçons organisées par jour. */
    public function program(Request $request, FitnessProgram $program)
    {
        abort_unless($program->is_published, 404);
        $done = $this->completedLessonIds($request)->flip();
        $program->load('lessons');
        $program->lessons->each(fn ($l) => $l->setAttribute('completed', $done->has($l->id)));
        return response()->json(['success' => true, 'data' => $program]);
    }

    /** POST /fitness/sessions : enregistre une séance réalisée. */
    public function storeSession(Request $request)
    {
        $data = $request->validate([
            'lesson_id' => 'required|integer|exists:fitness_lessons,id',
            'duration_minutes' => 'nullable|integer|min:1|max:600',
        ]);
        $lesson = FitnessLesson::findOrFail($data['lesson_id']);

        $session = FitnessSession::create([
            'user_id' => $request->user()->id,
            'fitness_lesson_id' => $lesson->id,
            'duration_minutes' => $data['duration_minutes'] ?? $lesson->duration_minutes,
            'completed_at' => now(),
        ]);

        return response()->json(['success' => true, 'data' => $session, 'stats' => $this->stats($request)], 201);
    }

    /** GET /fitness/sessions : historique et progression personnelle. */
    public function sessions(Request $request)
    {
        $sessions = FitnessSession::with('lesson.program:id,title,category,level,duration_days')
            ->where('user_id', $request->user()->id)
            ->latest('completed_at')
            ->limit(200)
            ->get();
        return response()->json(['success' => true, 'data' => $sessions, 'stats' => $this->stats($request)]);
    }

    private function completedLessonIds(Request $request)
    {
        return FitnessSession::where('user_id', $request->user()->id)->distinct()->pluck('fitness_lesson_id');
    }

    /** @return array<string,int> */
    private function stats(Request $request): array
    {
        $base = FitnessSession::where('user_id', $request->user()->id);
        $days = (clone $base)->selectRaw('DATE(completed_at) as d')->distinct()->orderByDesc('d')->pluck('d')
            ->map(fn ($d) => substr((string) $d, 0, 10))->all();

        // Série : jours consécutifs jusqu'à aujourd'hui (ou hier).
        $streak = 0;
        $cursor = now()->startOfDay();
        if ($days && $days[0] !== $cursor->toDateString()) {
            $cursor->subDay();
        }
        foreach ($days as $d) {
            if ($d !== $cursor->toDateString()) {
                break;
            }
            $streak++;
            $cursor->subDay();
        }

        return [
            'total_sessions' => (clone $base)->count(),
            'total_minutes' => (int) (clone $base)->sum('duration_minutes'),
            'active_days' => count($days),
            'current_streak' => $streak,
            'this_week' => (clone $base)->where('completed_at', '>=', now()->startOfWeek())->count(),
        ];
    }
}
