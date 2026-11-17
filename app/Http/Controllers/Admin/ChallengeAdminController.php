<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChallengeParticipant;
use App\Services\ChallengeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Panneau administrateur des DÉFIS DU MOIS : créer, modifier, programmer, clôturer, récompenser. */
class ChallengeAdminController extends Controller
{
    public function __construct(private ChallengeService $service)
    {
    }

    public function index()
    {
        $this->service->rollover();
        $challenges = Challenge::withCount('participants')->orderByDesc('starts_at')->orderBy('title')->paginate(20);
        return view('admin.challenges.index', compact('challenges'));
    }

    public function create()
    {
        return view('admin.challenges.form', ['challenge' => new Challenge([
            'starts_at' => now()->startOfMonth(),
            'ends_at' => now()->endOfMonth(),
            'target_days' => 21,
            'points_per_day' => 10,
            'completion_bonus' => 50,
            'is_published' => true,
            'recurring_monthly' => true,
            'type' => 'meditation',
        ])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('challenges', 'public');
        }
        Challenge::create($data);
        return redirect()->route('admin.challenges.index')->with('success', 'Défi créé.');
    }

    public function show(Challenge $challenge)
    {
        $participants = $challenge->leaderboardQuery()->paginate(50);
        return view('admin.challenges.show', compact('challenge', 'participants'));
    }

    public function edit(Challenge $challenge)
    {
        return view('admin.challenges.form', compact('challenge'));
    }

    public function update(Request $request, Challenge $challenge)
    {
        $data = $this->validated($request);
        if ($request->hasFile('image')) {
            if ($challenge->image && !str_starts_with($challenge->image, 'http')) {
                Storage::disk('public')->delete($challenge->image);
            }
            $data['image'] = $request->file('image')->store('challenges', 'public');
        }
        $challenge->update($data);
        return redirect()->route('admin.challenges.show', $challenge)->with('success', 'Défi mis à jour.');
    }

    public function destroy(Challenge $challenge)
    {
        $challenge->delete();
        return redirect()->route('admin.challenges.index')->with('success', 'Défi supprimé.');
    }

    /** Clôture manuelle : fige le classement final. */
    public function close(Challenge $challenge)
    {
        if ($challenge->closed_at) {
            return back()->with('error', 'Ce défi est déjà clôturé.');
        }
        $this->service->close($challenge);
        return back()->with('success', 'Défi clôturé : le classement final est figé.');
    }

    /** Marque la récompense d'un gagnant comme attribuée (ou annule). */
    public function toggleReward(Challenge $challenge, ChallengeParticipant $participant)
    {
        abort_unless($participant->challenge_id === $challenge->id, 404);
        $participant->update(['reward_granted' => !$participant->reward_granted]);
        return back()->with('success', $participant->reward_granted ? 'Récompense attribuée.' : 'Récompense retirée.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'objective' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'type' => ['required', Rule::in(array_keys(Challenge::TYPES))],
            'target_days' => 'required|integer|min:1|max:31',
            'points_per_day' => 'required|integer|min:0|max:1000',
            'completion_bonus' => 'required|integer|min:0|max:10000',
            'badge_name' => 'nullable|string|max:255',
            'reward' => 'nullable|string|max:2000',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after_or_equal:starts_at',
            'image' => 'nullable|image|max:5120',
        ]);
        unset($data['image']);
        $data['is_published'] = $request->boolean('is_published');
        $data['recurring_monthly'] = $request->boolean('recurring_monthly');
        return $data;
    }
}
