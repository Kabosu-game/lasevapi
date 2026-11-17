<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OracleDraw;
use App\Services\OracleService;
use Illuminate\Http\Request;

class OracleController extends Controller
{
    public function __construct(private OracleService $oracle)
    {
    }

    /** GET /oracle/status */
    public function status(Request $request)
    {
        return response()->json(['success' => true, 'data' => $this->oracle->status($request->user())]);
    }

    /** POST /oracle/draw — body : personalize (bool), goals (liste), age (int) */
    public function draw(Request $request)
    {
        $data = $request->validate([
            'personalize' => 'boolean',
            'goals' => 'nullable|array|max:20',
            'goals.*' => 'string|max:120',
            'age' => 'nullable|integer|min:1|max:120',
        ]);
        $user = $request->user();

        // Seules les informations autorisées par l'utilisateur sont utilisées.
        $profile = ($data['personalize'] ?? true) ? array_filter([
            'name' => $user->name !== 'Membre LASÈV' ? $user->name : null,
            'age' => $data['age'] ?? $user->date_of_birth?->age,
            'goals' => $data['goals'] ?? $user->objectives()->pluck('name')->all(),
        ]) : [];

        try {
            $draw = $this->oracle->draw($user, $profile);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => $this->oracle->status($user),
            ], 409);
        }

        return response()->json([
            'success' => true,
            'data' => ['draw' => $draw, 'status' => $this->oracle->status($user)],
        ], 201);
    }

    /** GET /oracle/history */
    public function history(Request $request)
    {
        $draws = OracleDraw::with('card')
            ->where('user_id', $request->user()->id)
            ->latest('drawn_at')
            ->limit(100)
            ->get();
        return response()->json(['success' => true, 'data' => $draws]);
    }
}
