<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityCategory;
use App\Services\CompanionService;
use Illuminate\Http\Request;

/**
 * COMPAGNON LASÈV (sans IA) :
 *  A. accueil et messages choisis parmi ceux écrits dans l'admin (Compagnon > Messages),
 *  B. rappels à partir des activités non accomplies envoyées par l'app,
 *  C. bilan énergétique quotidien (évaluation déclarative), D. recommandations par règles simples.
 * Aucun texte n'est généré : tout ce que l'utilisateur lit a été écrit par l'équipe.
 */
class AssistantController extends Controller
{
    public function __construct(private CompanionService $companion)
    {
    }

    /** POST /assistant/greeting */
    public function greeting(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'nullable|string|max:80',
            'goals' => 'nullable|array|max:20',
            'goals.*' => 'string|max:120',
            'pending' => 'nullable|array|max:20',
            'pending.*' => 'string|max:160',
            'done_today' => 'nullable|array|max:20',
            'done_today.*' => 'string|max:160',
            'missed_yesterday' => 'nullable|array|max:20',
            'missed_yesterday.*' => 'string|max:160',
            'hour' => 'nullable|integer|min:0|max:23',
        ]);
        $result = $this->companion->forUser($request->user(), $data);

        return response()->json([
            'success' => true,
            'ai' => false,
            'data' => $result,
        ]);
    }

    /** GET /companion/notifications : règles des rappels à programmer sur le téléphone. */
    public function notifications(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->companion->notificationPlan($request->user()),
        ]);
    }

    /** POST /assistant/checkin : bilan quotidien → analyse + recommandations. */
    public function checkin(Request $request)
    {
        $data = $request->validate([
            'mood' => 'required|integer|min:1|max:5',
            'energy' => 'required|integer|min:1|max:5',
            'sleep' => 'required|integer|min:1|max:5',
            'emotion' => 'required|string|max:60',
            'stress' => 'required|integer|min:1|max:5',
            'wants_activity' => 'required|boolean',
            'note' => 'nullable|string|max:1000',
            'first_name' => 'nullable|string|max:80',
            'goals' => 'nullable|array|max:20',
            'goals.*' => 'string|max:120',
        ]);

        return response()->json([
            'success' => true,
            'ai' => false,
            'data' => $this->withKnownCategories($this->fallbackCheckin($data)),
        ]);
    }

    /** Clés des catégories Activités visibles dans l'app (gérées dans l'admin). @return list<string> */
    private function musicCategories(): array
    {
        return ActivityCategory::activeKeys() ?: ['meditation'];
    }

    /**
     * Les recommandations ne renvoient que des catégories existantes (une catégorie
     * masquée ou supprimée dans l'admin est remplacée par la première disponible).
     */
    private function withKnownCategories(array $result): array
    {
        $known = $this->musicCategories();
        $result['recommendations'] = array_map(function ($rec) use ($known) {
            if (!in_array($rec['category'] ?? null, $known, true)) {
                $rec['category'] = $known[0];
            }
            return $rec;
        }, $result['recommendations'] ?? []);
        return $result;
    }

    /** Recommandations par règles simples, selon les réponses du bilan. @param array<string,mixed> $d */
    private function fallbackCheckin(array $d): array
    {
        $recs = [];
        $low = $d['energy'] <= 2;
        $stressed = $d['stress'] >= 4;

        if ($stressed || $low) {
            $recs[] = ['type' => 'breathing', 'title' => 'Respiration apaisante', 'reason' => 'Quelques minutes de respiration lente aident à relâcher la tension.', 'duration_minutes' => 5, 'category' => 'respiration'];
            $recs[] = ['type' => 'meditation', 'title' => 'Méditation douce', 'reason' => 'Un temps calme pour te recentrer en douceur.', 'duration_minutes' => 10, 'category' => 'meditation'];
        }
        if ($d['sleep'] <= 2) {
            $recs[] = ['type' => 'music', 'title' => 'Musique pour le sommeil ce soir', 'reason' => 'Ta nuit a été difficile : prépare un endormissement serein.', 'duration_minutes' => 20, 'category' => 'sommeil'];
        }
        if ($d['wants_activity'] && !$low) {
            $recs[] = ['type' => 'yoga', 'title' => 'Séance de yoga', 'reason' => 'Ton énergie permet un moment de mouvement conscient.', 'duration_minutes' => 20, 'category' => 'meditation'];
            $recs[] = ['type' => 'physical', 'title' => 'Jogging en musique', 'reason' => 'Bouger stimule l\'énergie et l\'humeur.', 'duration_minutes' => 25, 'category' => 'jogging'];
        }
        if ($d['mood'] <= 2) {
            $recs[] = ['type' => 'introspection', 'title' => 'Écrire dans ton journal', 'reason' => 'Mettre des mots sur ce que tu ressens peut alléger ta journée.', 'duration_minutes' => 10, 'category' => 'relaxation'];
        }
        if (!$recs) {
            $recs[] = ['type' => 'music', 'title' => 'Musique de concentration', 'reason' => 'Profite de ta bonne forme pour avancer sur tes projets.', 'duration_minutes' => 30, 'category' => 'concentration'];
            $recs[] = ['type' => 'challenge', 'title' => 'Valider ton défi du jour', 'reason' => 'Ta régularité te rapproche de ton badge.', 'duration_minutes' => 5, 'category' => 'meditation'];
        }

        $energyLabels = [1 => 'très faible', 2 => 'faible', 3 => 'moyen', 4 => 'bon', 5 => 'très bon'];
        $summary = 'Aujourd\'hui, ton niveau d\'énergie déclaré est ' . $energyLabels[$d['energy']] . '. ';
        $summary .= $stressed ? 'Tu ressens du stress : accorde-toi des moments de calme. ' : 'Prends soin de cet équilibre. ';
        $summary .= 'Voici quelques propositions adaptées, libre à toi de les suivre ou non.';

        return ['summary' => $summary, 'recommendations' => array_slice($recs, 0, 4)];
    }
}
