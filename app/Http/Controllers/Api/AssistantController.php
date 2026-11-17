<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityCategory;
use App\Services\AiCompanion;
use Illuminate\Http\Request;

/**
 * ASSISTANT IA — compagnon personnel LASÈV :
 *  A. accueil personnalisé, B. rappels intelligents (à partir des activités non accomplies
 *  envoyées par l'app), C. bilan énergétique quotidien (évaluation déclarative), D. recommandations.
 */
class AssistantController extends Controller
{
    private const RECOMMENDATION_TYPES = ['meditation', 'music', 'yoga', 'physical', 'breathing', 'introspection', 'challenge'];

    public function __construct(private AiCompanion $ai)
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
        $name = trim($data['first_name'] ?? '') ?: ($request->user()->name !== 'Membre LASÈV' ? $request->user()->name : '');

        $message = $this->ai->json(
            'Écris le message d\'accueil du jour pour l\'utilisateur : salue-le par son prénom, '
            . 'félicite les efforts déjà accomplis, rappelle avec douceur les activités prévues ou non accomplies hier, '
            . 'et encourage-le à reprendre sa routine s\'il l\'a interrompue. 2 à 4 phrases, sans liste.',
            json_encode([
                'prenom' => $name ?: null,
                'heure_locale' => $data['hour'] ?? null,
                'objectifs' => $data['goals'] ?? [],
                'activites_prevues_aujourdhui' => $data['pending'] ?? [],
                'deja_fait_aujourdhui' => $data['done_today'] ?? [],
                'non_accompli_hier' => $data['missed_yesterday'] ?? [],
            ], JSON_UNESCAPED_UNICODE),
            [
                'type' => 'object',
                'properties' => ['message' => ['type' => 'string']],
                'required' => ['message'],
                'additionalProperties' => false,
            ],
            1200,
        )['message'] ?? null;

        return response()->json([
            'success' => true,
            'ai' => $message !== null,
            'data' => ['message' => $message ?? $this->fallbackGreeting($name, $data)],
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

        $result = $this->ai->json(
            'L\'utilisateur vient de remplir son bilan énergétique quotidien (évaluation déclarative de son bien-être, '
            . 'notes de 1 = très bas à 5 = très haut ; pour le stress, 5 = très stressé). '
            . 'Rédige une courte analyse bienveillante (2 à 3 phrases, en citant son niveau d\'énergie déclaré) puis propose '
            . '2 à 4 recommandations concrètes et adaptées, de durée réaliste. '
            . 'Types : meditation, music (préciser category), yoga, physical, breathing, introspection, challenge. '
            . 'Catégories de l\'espace Activités : ' . $this->categoryList() . '. '
            . 'Si l\'utilisateur ne souhaite pas d\'activité, privilégie des propositions très douces et courtes. '
            . 'Rappelle-toi qu\'il s\'agit d\'une évaluation déclarative, pas d\'un diagnostic.',
            json_encode($data, JSON_UNESCAPED_UNICODE),
            [
                'type' => 'object',
                'properties' => [
                    'summary' => ['type' => 'string'],
                    'recommendations' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'type' => ['type' => 'string', 'enum' => self::RECOMMENDATION_TYPES],
                                'title' => ['type' => 'string'],
                                'reason' => ['type' => 'string'],
                                'duration_minutes' => ['type' => 'integer'],
                                'category' => ['type' => 'string', 'enum' => $this->musicCategories()],
                            ],
                            'required' => ['type', 'title', 'reason', 'duration_minutes', 'category'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                'required' => ['summary', 'recommendations'],
                'additionalProperties' => false,
            ],
        );

        $valid = is_array($result)
            && is_string($result['summary'] ?? null)
            && is_array($result['recommendations'] ?? null)
            && $result['recommendations'] !== [];

        return response()->json([
            'success' => true,
            'ai' => $valid,
            'data' => $this->withKnownCategories($valid ? $result : $this->fallbackCheckin($data)),
        ]);
    }

    /** POST /assistant/chat */
    public function chat(Request $request)
    {
        $data = $request->validate([
            'messages' => 'required|array|min:1|max:30',
            'messages.*.role' => 'required|in:user,assistant',
            'messages.*.content' => 'required|string|max:2000',
            'first_name' => 'nullable|string|max:80',
            'goals' => 'nullable|array|max:20',
            'goals.*' => 'string|max:120',
            // Porte en cours du parcours « Face à toi » quand la discussion est ouverte depuis ce parcours.
            'topic' => 'nullable|string|max:300',
        ]);

        $messages = array_values(array_map(
            fn ($m) => ['role' => $m['role'], 'content' => $m['content']],
            $data['messages'],
        ));
        // La conversation doit commencer par l'utilisateur et s'y terminer.
        while ($messages && $messages[0]['role'] !== 'user') {
            array_shift($messages);
        }
        if (!$messages || end($messages)['role'] !== 'user') {
            return response()->json(['success' => false, 'message' => 'Le dernier message doit venir de l\'utilisateur.'], 422);
        }

        $context = 'Prénom : ' . ($data['first_name'] ?? 'inconnu')
            . '. Objectifs : ' . (implode(', ', $data['goals'] ?? []) ?: 'non précisés') . '.';

        $topic = trim($data['topic'] ?? '');
        $guide = $topic === '' ? '' : ' L\'utilisateur parcourt « Face à toi – Les 7 Portes Intérieures », un parcours '
            . 'd\'introspection inspiré de la symbolique des chakras. Il travaille sur : « ' . $topic . ' ». '
            . 'Guide-le sur ce thème avec des questions ouvertes et bienveillantes, une à la fois, sans jugement ni '
            . 'interprétation définitive. Présente les chakras comme des symboles de réflexion, jamais comme une réalité médicale.';

        $reply = $this->ai->chat(
            'Tu réponds dans le chat de l\'application. ' . $context . $guide
            . ' Tu peux suggérer les espaces de l\'app : Oracles, Activités (' . $this->categoryNames() . '), Fitness Center, My Journal, Défis du mois, Face à toi (7 Portes Intérieures), '
            . 'Espace Sacré des Lois (bibliothèque de rituels). Réponses courtes (5 phrases maximum).',
            $messages,
        );

        $fallback = $topic === ''
            ? 'Je ne peux pas te répondre pour le moment. En attendant, tu peux faire le bilan du jour ou prendre quelques respirations profondes : inspire 4 secondes, expire 6 secondes.'
            : 'Je ne peux pas te répondre pour le moment. En attendant, prends quelques respirations lentes et écris ce que ce thème éveille en toi : une émotion, un souvenir, une question. Il n\'y a pas de bonne ou de mauvaise réponse.';

        return response()->json([
            'success' => true,
            'ai' => $reply !== null,
            'data' => ['reply' => $reply ?? $fallback],
        ]);
    }

    /** @param array<string,mixed> $data */
    private function fallbackGreeting(string $name, array $data): string
    {
        $hour = $data['hour'] ?? (int) now()->format('G');
        $hello = $hour < 18 ? 'Bonjour' : 'Bonsoir';
        $text = trim($hello . ' ' . $name) . '. Heureuse de te retrouver aujourd\'hui. ';

        if (!empty($data['done_today'])) {
            $text .= 'Bravo pour ce que tu as déjà accompli : ' . implode(', ', $data['done_today']) . '. ';
        } elseif (!empty($data['missed_yesterday'])) {
            $text .= 'Hier a été plus calme, et c\'est normal : chaque jour est une nouvelle occasion de reprendre ta routine. ';
        } else {
            $text .= 'Ton évolution commence par les petites décisions que tu prends chaque matin. ';
        }
        if (!empty($data['pending'])) {
            $text .= 'Voici ton programme du jour.';
        }
        return $text;
    }

    /** Clés des catégories Activités visibles dans l'app (gérées dans l'admin). @return list<string> */
    private function musicCategories(): array
    {
        return ActivityCategory::activeKeys() ?: ['meditation'];
    }

    /** « clé (Nom), … » pour guider l'IA. */
    private function categoryList(): string
    {
        $names = ActivityCategory::where('is_active', true)->ordered()->pluck('name', 'key');
        return $names->map(fn ($name, $key) => $key . ' (' . $name . ')')->implode(', ');
    }

    /** « Méditation, Jogging, … » pour le chat. */
    private function categoryNames(): string
    {
        return ActivityCategory::where('is_active', true)->ordered()->pluck('name')->implode(', ');
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

    /** Recommandations par règles simples quand l'IA est indisponible. @param array<string,mixed> $d */
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
