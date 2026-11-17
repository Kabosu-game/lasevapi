<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InnerGate;
use App\Models\InnerGateStep;
use App\Services\InnerGateService;
use Illuminate\Http\Request;

/** FACE À TOI — Les 7 Portes Intérieures. */
class InnerGateController extends Controller
{
    private const LOCKED = 'Cette porte s\'ouvrira quand tu auras terminé la précédente.';

    public function __construct(private InnerGateService $gates)
    {
    }

    /** GET /inner-gates : portes, progression et message du compagnon. */
    public function index(Request $request)
    {
        return response()->json(['success' => true, 'data' => $this->gates->overview($request->user())]);
    }

    /** GET /inner-gates/{gate} : textes et étapes d'une porte ouverte. */
    public function show(Request $request, InnerGate $gate)
    {
        abort_unless($gate->is_active, 404);
        $state = $this->gates->gateState($request->user(), $gate);
        if ($state['status'] === 'locked') {
            return response()->json(['success' => false, 'message' => self::LOCKED, 'data' => ['status' => 'locked']], 409);
        }

        return response()->json(['success' => true, 'data' => $this->gates->detail($request->user(), $gate, $state)]);
    }

    /** POST /inner-gates/steps/{step}/complete */
    public function completeStep(Request $request, InnerGateStep $step)
    {
        $gate = $step->gate;
        abort_unless($gate && $gate->is_active, 404);
        $user = $request->user();

        $state = $this->gates->gateState($user, $gate);
        if ($state['status'] === 'locked') {
            return response()->json(['success' => false, 'message' => self::LOCKED], 409);
        }
        $current = $this->gates->steps($user, $gate, $state['status'] === 'completed')->firstWhere('id', $step->id);
        if (!$current['available']) {
            return response()->json(['success' => false, 'message' => 'Termine d\'abord l\'étape précédente.'], 409);
        }

        $gateCompleted = $this->gates->complete($user, $step);
        $overview = $this->gates->overview($user);
        $nextGate = $gateCompleted
            ? collect($overview['gates'])->first(fn ($g) => $g['number'] > $gate->number && in_array($g['status'], ['available', 'in_progress', 'empty'], true))
            : null;

        return response()->json([
            'success' => true,
            'data' => [
                'gate' => $this->gates->detail($user, $gate, collect($overview['gates'])->firstWhere('id', $gate->id)),
                'gate_completed' => $gateCompleted,
                'next_gate' => $nextGate,
                'summary' => $overview['summary'],
            ],
        ]);
    }
}
