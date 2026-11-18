<?php

namespace App\Services;

use App\Models\OracleCard;
use App\Models\OracleDraw;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Règles du tirage ORACLES (cahier des charges) :
 *  - Fréquence : un tirage tous les 3 jours.
 *  - Unicité : une carte n'est pas tirée deux fois par le même utilisateur dans le même mois.
 *  - Historique : chaque tirage est conservé.
 *  - Renouvellement : nouveau cycle chaque mois (le cycle = AAAA-MM).
 * Tout est stocké côté serveur, donc indépendant du téléphone.
 */
class OracleService
{
    public const INTERVAL_DAYS = 3;

    public function cycle(?CarbonInterface $at = null): string
    {
        return ($at ?? now())->format('Y-m');
    }

    public function lastDraw(User $user): ?OracleDraw
    {
        return OracleDraw::with('card')->where('user_id', $user->id)->latest('drawn_at')->first();
    }

    public function nextDrawAt(User $user): ?CarbonInterface
    {
        $last = $this->lastDraw($user);
        return $last ? $last->drawn_at->copy()->addDays(self::INTERVAL_DAYS) : null;
    }

    /** Cartes encore disponibles pour l'utilisateur dans le cycle courant. */
    public function availableCards(User $user)
    {
        $used = OracleDraw::where('user_id', $user->id)
            ->where('cycle', $this->cycle())
            ->pluck('oracle_card_id');

        return OracleCard::where('is_active', true)->whereNotIn('id', $used);
    }

    /** @return array<string,mixed> */
    public function status(User $user): array
    {
        $next = $this->nextDrawAt($user);
        $remaining = $this->availableCards($user)->count();
        $canDraw = ($next === null || now()->gte($next)) && $remaining > 0;

        return [
            'can_draw' => $canDraw,
            'next_draw_at' => $next?->toIso8601String(),
            'interval_days' => self::INTERVAL_DAYS,
            'cycle' => $this->cycle(),
            'draws_this_cycle' => OracleDraw::where('user_id', $user->id)->where('cycle', $this->cycle())->count(),
            'remaining_cards' => $remaining,
            'deck_size' => OracleCard::where('is_active', true)->count(),
            'last_draw' => $this->lastDraw($user),
        ];
    }

    /**
     * Effectue un tirage. Le verrou sur la ligne utilisateur empêche deux tirages simultanés ;
     * l'index unique (user, carte, cycle) garantit l'absence de doublon en base.
     *
     * @param array<string,mixed> $profile informations de profil que l'utilisateur a autorisées
     * @throws \RuntimeException si le tirage n'est pas encore disponible
     */
    public function draw(User $user, array $profile): OracleDraw
    {
        $draw = DB::transaction(function () use ($user) {
            User::whereKey($user->id)->lockForUpdate()->first();

            $next = $this->nextDrawAt($user);
            if ($next !== null && now()->lt($next)) {
                throw new \RuntimeException('Ton prochain tirage sera disponible le ' . $next->translatedFormat('d/m/Y à H:i') . '.');
            }

            $card = $this->availableCards($user)->inRandomOrder()->first();
            if (!$card) {
                throw new \RuntimeException('Tu as déjà découvert toutes les cartes de ce mois. Un nouveau cycle commence le mois prochain.');
            }

            try {
                return OracleDraw::create([
                    'user_id' => $user->id,
                    'oracle_card_id' => $card->id,
                    'cycle' => $this->cycle(),
                    'drawn_at' => now(),
                ]);
            } catch (QueryException $e) {
                throw new \RuntimeException('Ce tirage a déjà été effectué. Réessaie dans un instant.');
            }
        });

        $draw->personalized_interpretation = $this->personalize($draw->card, $profile);
        $draw->save();

        return $draw->load('card');
    }

    /** @param array<string,mixed> $profile */
    private function personalize(OracleCard $card, array $profile): string
    {
        $name = trim((string) ($profile['name'] ?? ''));
        $goals = array_values(array_filter(array_map('strval', (array) ($profile['goals'] ?? []))));

        // Interprétation écrite dans l'admin, adressée à l'utilisateur et reliée à ses objectifs (aucun texte généré).
        $intro = $name !== '' ? $name . ', ' : '';
        $text = $intro . lcfirst($card->interpretation);
        if ($goals) {
            $text .= ' Dans ton chemin vers « ' . $goals[array_rand($goals)] . ' », cette carte peut t\'aider à prendre un peu de recul.';
        }
        return $text . ' Garde ce message comme une piste de réflexion pour les jours à venir.';
    }
}
