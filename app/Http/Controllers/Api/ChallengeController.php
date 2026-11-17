<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChallengeParticipant;
use App\Services\ChallengeService;
use Illuminate\Http\Request;

class ChallengeController extends Controller
{
    public function __construct(private ChallengeService $challenges)
    {
    }

    /** GET /challenges : défis en cours et à venir, avec la progression de l'utilisateur. */
    public function index(Request $request)
    {
        $this->challenges->rollover();
        $user = $request->user();

        $list = Challenge::where('is_published', true)
            ->whereNull('closed_at')
            ->whereDate('ends_at', '>=', now()->toDateString())
            ->withCount('participants')
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        $mine = ChallengeParticipant::where('user_id', $user->id)
            ->whereIn('challenge_id', $list->pluck('id'))
            ->get()
            ->keyBy('challenge_id');

        $data = $list->map(fn (Challenge $c) => $this->present($c, $mine->get($c->id)));

        return response()->json([
            'success' => true,
            'data' => $data,
            'summary' => $this->challenges->summary($user),
        ]);
    }

    /** GET /challenges/{challenge} : détail et classement. */
    public function show(Request $request, Challenge $challenge)
    {
        abort_unless($challenge->is_published, 404);
        $user = $request->user();
        $participant = ChallengeParticipant::where('challenge_id', $challenge->id)->where('user_id', $user->id)->first();

        // Rang ex æquo : mêmes points et mêmes jours = même rang (cohérent avec my_rank).
        $rank = 0;
        $previous = null;
        $leaderboard = $challenge->leaderboardQuery()->limit(50)->get()->values()->map(function ($p, $i) use (&$rank, &$previous, $user) {
            $key = $p->points . ':' . $p->days_completed;
            if ($key !== $previous) {
                $rank = $i + 1;
                $previous = $key;
            }
            return [
                'rank' => $rank,
                'name' => $this->displayName($p->user?->name),
                'points' => $p->points,
                'days_completed' => $p->days_completed,
                'completed' => (bool) $p->completed_at,
                'is_me' => $p->user_id === $user->id,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $this->present($challenge->loadCount('participants'), $participant) + [
                'leaderboard' => $leaderboard,
                'my_rank' => $participant ? $this->challenges->rankOf($challenge, $participant) : null,
            ],
        ]);
    }

    /** POST /challenges/{challenge}/join */
    public function join(Request $request, Challenge $challenge)
    {
        abort_unless($challenge->is_published, 404);
        try {
            $participant = $this->challenges->join($challenge, $request->user());
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        }
        return response()->json(['success' => true, 'data' => $this->present($challenge->loadCount('participants'), $participant)]);
    }

    /** POST /challenges/{challenge}/checkin : valide la journée. */
    public function checkin(Request $request, Challenge $challenge)
    {
        abort_unless($challenge->is_published, 404);
        try {
            $participant = $this->challenges->checkin($challenge, $request->user());
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        }
        return response()->json([
            'success' => true,
            'data' => $this->present($challenge->loadCount('participants'), $participant) + [
                'my_rank' => $this->challenges->rankOf($challenge, $participant),
            ],
        ]);
    }

    /** GET /challenges-summary : points, badges et récompenses. */
    public function summary(Request $request)
    {
        return response()->json(['success' => true, 'data' => $this->challenges->summary($request->user())]);
    }

    /** @return array<string,mixed> */
    private function present(Challenge $c, ?ChallengeParticipant $p): array
    {
        return [
            'id' => $c->id,
            'title' => $c->title,
            'description' => $c->description,
            'objective' => $c->objective,
            'type' => $c->type,
            'target_days' => $c->target_days,
            'points_per_day' => $c->points_per_day,
            'completion_bonus' => $c->completion_bonus,
            'badge_name' => $c->badge_name,
            'reward' => $c->reward,
            'image_url' => $c->image_url,
            'starts_at' => $c->starts_at?->toDateString(),
            'ends_at' => $c->ends_at?->toDateString(),
            'status' => $c->status,
            'participants_count' => $c->participants_count ?? $c->participants()->count(),
            'me' => $p ? [
                'joined' => true,
                'days_completed' => $p->days_completed,
                'points' => $p->points,
                'checked_in_today' => $p->last_checkin_date?->isToday() ?? false,
                'completed' => (bool) $p->completed_at,
            ] : ['joined' => false, 'days_completed' => 0, 'points' => 0, 'checked_in_today' => false, 'completed' => false],
        ];
    }

    /** Classement public : prénom + initiale uniquement. */
    private function displayName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '' || $name === 'Membre LASÈV') {
            return 'Membre LASÈV';
        }
        $parts = preg_split('/\s+/', $name);
        $first = $parts[0];
        $initial = isset($parts[1]) ? ' ' . mb_strtoupper(mb_substr($parts[1], 0, 1)) . '.' : '';
        return $first . $initial;
    }
}
