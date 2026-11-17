<?php

namespace App\Services;

use App\Models\Challenge;
use App\Models\ChallengeCheckin;
use App\Models\ChallengeParticipant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Logique des DÉFIS DU MOIS : participation, validation quotidienne, points, badges,
 * classement, clôture et renouvellement automatique mensuel.
 */
class ChallengeService
{
    /**
     * Renouvellement automatique : chaque défi « récurrent mensuel » terminé est clôturé
     * (classement figé) et dupliqué sur le mois suivant. Appelé à la lecture des défis,
     * ce qui évite de dépendre d'une tâche planifiée sur le serveur.
     */
    public function rollover(): void
    {
        $ended = Challenge::where('recurring_monthly', true)
            ->where('is_published', true)
            ->whereNull('closed_at')
            ->whereDate('ends_at', '<', now()->toDateString())
            ->get();

        foreach ($ended as $challenge) {
            DB::transaction(function () use ($challenge) {
                $fresh = Challenge::whereKey($challenge->id)->lockForUpdate()->first();
                if (!$fresh || $fresh->closed_at) {
                    return; // déjà traité par une requête concurrente
                }
                $this->close($fresh);

                // Nouveau défi sur le mois qui suit la fin du précédent (ou le mois courant si on a du retard).
                $start = $fresh->ends_at->copy()->addDay()->startOfMonth();
                if ($start->lt(now()->startOfMonth())) {
                    $start = now()->startOfMonth();
                }
                $end = $start->copy()->endOfMonth();

                $next = $fresh->replicate(['closed_at', 'status', 'image_url']);
                $next->starts_at = $start;
                $next->ends_at = $end;
                $next->target_days = min($fresh->target_days, $start->daysInMonth);
                $next->parent_challenge_id = $fresh->id;
                $next->closed_at = null;
                $next->save();
            });
        }
    }

    /** Clôture : fige le rang final de chaque participant. */
    public function close(Challenge $challenge): void
    {
        $rank = 0;
        foreach ($challenge->leaderboardQuery()->get() as $participant) {
            $participant->update(['final_rank' => ++$rank]);
        }
        $challenge->update(['closed_at' => now()]);
    }

    public function join(Challenge $challenge, User $user): ChallengeParticipant
    {
        if (!in_array($challenge->status, ['active', 'scheduled'], true)) {
            throw new \RuntimeException('Ce défi n\'accepte plus de participants.');
        }
        return ChallengeParticipant::firstOrCreate(['challenge_id' => $challenge->id, 'user_id' => $user->id]);
    }

    /** Valide la journée du jour pour l'utilisateur (une seule fois par jour). */
    public function checkin(Challenge $challenge, User $user): ChallengeParticipant
    {
        if (!$challenge->isOpenForCheckin()) {
            throw new \RuntimeException('Ce défi n\'est pas en cours.');
        }

        return DB::transaction(function () use ($challenge, $user) {
            $participant = ChallengeParticipant::firstOrCreate(
                ['challenge_id' => $challenge->id, 'user_id' => $user->id]
            );
            $participant = ChallengeParticipant::whereKey($participant->id)->lockForUpdate()->first();

            $today = now()->toDateString();
            if ($participant->last_checkin_date?->toDateString() === $today) {
                throw new \RuntimeException('Tu as déjà validé ta journée pour ce défi. À demain !');
            }
            if ($participant->days_completed >= $challenge->target_days) {
                throw new \RuntimeException('Bravo, tu as déjà accompli ce défi !');
            }

            try {
                ChallengeCheckin::create(['challenge_participant_id' => $participant->id, 'checkin_date' => $today]);
            } catch (QueryException $e) {
                throw new \RuntimeException('Tu as déjà validé ta journée pour ce défi. À demain !');
            }

            $participant->days_completed += 1;
            $participant->points += $challenge->points_per_day;
            $participant->last_checkin_date = $today;
            if ($participant->days_completed >= $challenge->target_days && !$participant->completed_at) {
                $participant->completed_at = now();
                $participant->points += $challenge->completion_bonus;
            }
            $participant->save();

            return $participant;
        });
    }

    public function rankOf(Challenge $challenge, ChallengeParticipant $participant): int
    {
        return $challenge->participants()
            ->where(function ($q) use ($participant) {
                $q->where('points', '>', $participant->points)
                    ->orWhere(function ($q) use ($participant) {
                        $q->where('points', $participant->points)
                            ->where('days_completed', '>', $participant->days_completed);
                    });
            })
            ->count() + 1;
    }

    /** @return array<string,mixed> points cumulés et badges obtenus par l'utilisateur */
    public function summary(User $user): array
    {
        $participations = ChallengeParticipant::with('challenge')->where('user_id', $user->id)->get();

        $badges = $participations
            ->filter(fn ($p) => $p->completed_at && $p->challenge && $p->challenge->badge_name)
            ->map(fn ($p) => [
                'name' => $p->challenge->badge_name,
                'challenge' => $p->challenge->title,
                'earned_at' => $p->completed_at->toIso8601String(),
            ])
            ->values();

        $rewards = $participations
            ->filter(fn ($p) => $p->reward_granted && $p->challenge)
            ->map(fn ($p) => ['challenge' => $p->challenge->title, 'reward' => $p->challenge->reward, 'rank' => $p->final_rank])
            ->values();

        return [
            'total_points' => (int) $participations->sum('points'),
            'challenges_joined' => $participations->count(),
            'challenges_completed' => $participations->whereNotNull('completed_at')->count(),
            'badges' => $badges,
            'rewards' => $rewards,
        ];
    }
}
