<?php

namespace App\Services;

use App\Models\InnerGate;
use App\Models\InnerGateCompletion;
use App\Models\InnerGateProgress;
use App\Models\InnerGateStep;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * FACE À TOI — règles du parcours des 7 Portes :
 *  - la première porte active est toujours ouverte ;
 *  - une porte s'ouvre quand la précédente est terminée (ou si la précédente n'a encore aucune étape) ;
 *  - dans une porte, les étapes se font dans l'ordre ;
 *  - une porte terminée le reste, même si l'admin ajoute des étapes ensuite.
 */
class InnerGateService
{
    public const DEFAULT_COMPANION = 'Chaque niveau te rapproche de ta vérité. Avance à ton rythme.';

    /** @return array{gates: list<array<string,mixed>>, summary: array<string,mixed>} */
    public function overview(User $user): array
    {
        $gates = InnerGate::where('is_active', true)->withCount('steps')->orderBy('number')->get();
        $doneByGate = $this->doneCountsByGate($user);
        $completedIds = InnerGateCompletion::where('user_id', $user->id)->pluck('inner_gate_id')->flip();

        $rows = [];
        $open = true;
        $stepsTotal = 0;
        $stepsDone = 0;
        foreach ($gates as $gate) {
            $total = (int) $gate->steps_count;
            $done = min($total, (int) ($doneByGate[$gate->id] ?? 0));
            $completed = $completedIds->has($gate->id) || ($total > 0 && $done >= $total);

            $status = match (true) {
                $completed => 'completed',
                !$open => 'locked',
                $total === 0 => 'empty',
                $done > 0 => 'in_progress',
                default => 'available',
            };

            $rows[] = [
                'id' => $gate->id,
                'number' => $gate->number,
                'name' => $gate->name,
                'theme' => $gate->theme,
                'color' => $gate->color,
                'image_url' => $gate->image_url,
                'companion_message' => $gate->companion_message,
                'steps_total' => $total,
                'steps_done' => $completed ? $total : $done,
                'progress' => $completed ? 1.0 : ($total > 0 ? round($done / $total, 2) : 0.0),
                'status' => $status,
            ];

            $stepsTotal += $total;
            $stepsDone += $completed ? $total : $done;
            $open = $completed || ($open && $total === 0);
        }

        $current = collect($rows)->first(fn ($g) => in_array($g['status'], ['available', 'in_progress'], true));
        $completedCount = collect($rows)->where('status', 'completed')->count();
        $allDone = $rows !== [] && $completedCount === count($rows);

        return [
            'gates' => $rows,
            'summary' => [
                'progress' => $stepsTotal > 0 ? round($stepsDone / $stepsTotal, 2) : 0.0,
                'completed_gates' => $completedCount,
                'total_gates' => count($rows),
                'current_gate' => $current['number'] ?? null,
                'finished' => $allDone,
                'companion_message' => ($current['companion_message'] ?? null)
                    ?: ($allDone ? (end($rows)['companion_message'] ?? null) : null)
                    ?: self::DEFAULT_COMPANION,
            ],
        ];
    }

    /** État d'une porte pour l'utilisateur (ligne de l'aperçu). */
    public function gateState(User $user, InnerGate $gate): ?array
    {
        return collect($this->overview($user)['gates'])->firstWhere('id', $gate->id);
    }

    /** Étapes ordonnées avec « terminée » et « accessible ». */
    public function steps(User $user, InnerGate $gate, bool $gateCompleted): Collection
    {
        $steps = $gate->steps()->get();
        $done = InnerGateProgress::where('user_id', $user->id)
            ->whereIn('inner_gate_step_id', $steps->pluck('id'))
            ->pluck('inner_gate_step_id')
            ->flip();

        $previousDone = true;
        return $steps->map(function (InnerGateStep $step) use ($done, $gateCompleted, &$previousDone) {
            $completed = $done->has($step->id);
            $available = $gateCompleted || $completed || $previousDone;
            $previousDone = $previousDone && $completed;

            return [
                'id' => $step->id,
                'position' => $step->position,
                'type' => $step->type,
                'title' => $step->title,
                'content' => $step->content,
                'prompts' => array_values(array_filter($step->prompts ?? [], fn ($p) => is_string($p) && trim($p) !== '')),
                'duration_minutes' => $step->duration_minutes,
                'media' => $step->media,
                'media_kind' => $step->media_kind,
                'completed' => $completed,
                'available' => $available,
            ];
        });
    }

    /** Détail d'une porte : textes, état et étapes. */
    public function detail(User $user, InnerGate $gate, array $state): array
    {
        return [
            'id' => $gate->id,
            'number' => $gate->number,
            'name' => $gate->name,
            'theme' => $gate->theme,
            'color' => $gate->color,
            'description' => $gate->description,
            'affirmation' => $gate->affirmation,
            'companion_message' => $gate->companion_message,
            'image_url' => $gate->image_url,
            'status' => $state['status'],
            'steps_total' => $state['steps_total'],
            'steps_done' => $state['steps_done'],
            'progress' => $state['progress'],
            'steps' => $this->steps($user, $gate, $state['status'] === 'completed')->values()->all(),
        ];
    }

    /** Valide une étape ; renvoie vrai si cette validation termine la porte. */
    public function complete(User $user, InnerGateStep $step): bool
    {
        return DB::transaction(function () use ($user, $step) {
            InnerGateProgress::firstOrCreate(
                ['user_id' => $user->id, 'inner_gate_step_id' => $step->id],
                ['completed_at' => now()],
            );

            $gateId = $step->inner_gate_id;
            if (InnerGateCompletion::where('user_id', $user->id)->where('inner_gate_id', $gateId)->exists()) {
                return false;
            }

            $stepIds = InnerGateStep::where('inner_gate_id', $gateId)->pluck('id');
            $done = InnerGateProgress::where('user_id', $user->id)->whereIn('inner_gate_step_id', $stepIds)->count();
            if ($stepIds->isEmpty() || $done < $stepIds->count()) {
                return false;
            }

            InnerGateCompletion::firstOrCreate(
                ['user_id' => $user->id, 'inner_gate_id' => $gateId],
                ['completed_at' => now()],
            );
            return true;
        });
    }

    /** @return Collection<int,int> nombre d'étapes faites par porte */
    private function doneCountsByGate(User $user): Collection
    {
        return InnerGateProgress::query()
            ->join('inner_gate_steps', 'inner_gate_steps.id', '=', 'inner_gate_progress.inner_gate_step_id')
            ->where('inner_gate_progress.user_id', $user->id)
            ->groupBy('inner_gate_steps.inner_gate_id')
            ->selectRaw('inner_gate_steps.inner_gate_id as gate_id, COUNT(*) as done')
            ->pluck('done', 'gate_id');
    }
}
