<?php

namespace App\Services;

use App\Models\ChallengeParticipant;
use App\Models\CompanionMessage;
use App\Models\CompanionMessageView;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * COMPAGNON : choisit, parmi les messages écrits dans l'admin, ceux qui correspondent à la
 * situation de l'utilisateur. Aucune génération de texte : seuls les messages de l'équipe sont affichés.
 */
class CompanionService
{
    public function __construct(private OracleService $oracle)
    {
    }

    /**
     * @param array{first_name?:string,hour?:int,pending?:array,done_today?:array,missed_yesterday?:array} $context
     * @return array{message:string, messages:list<array<string,mixed>>}
     */
    public function forUser(User $user, array $context): array
    {
        $now = now();
        $absentDays = $user->companion_last_seen_at
            ? (int) $user->companion_last_seen_at->copy()->startOfDay()->diffInDays($now->copy()->startOfDay())
            : 0;
        $user->forceFill(['companion_last_seen_at' => $now])->save();

        $facts = $this->facts($user, $context, $absentDays);
        $candidates = $this->candidates($user, $facts);

        $name = trim($context['first_name'] ?? '');
        if ($name === '' && $user->name !== 'Membre LASÈV') {
            $name = trim(explode(' ', (string) $user->name)[0]);
        }
        $replace = ['{prenom}' => $name, '{defi}' => $facts['challenge_title'] ?? ''];

        $shown = $candidates->take(3)->values();
        foreach ($shown as $message) {
            CompanionMessageView::create(['companion_message_id' => $message->id, 'user_id' => $user->id, 'shown_at' => $now]);
        }

        $out = $shown->map(fn (CompanionMessage $m) => [
            'id' => $m->id,
            'trigger' => $m->trigger,
            'title' => $m->title ? $this->fill($m->title, $replace) : null,
            'body' => $this->fill($m->body, $replace),
            'audio_url' => $m->audioUrl(),
            'action' => $m->action,
            'action_label' => $m->action_label,
        ])->all();

        $card = $out[0] ?? null;
        $hello = ($context['hour'] ?? (int) $now->format('G')) < 18 ? 'Bonjour' : 'Bonsoir';

        return [
            'message' => $card['body'] ?? trim($hello . ' ' . $name) . '. Voici ton programme du jour.',
            'messages' => $out,
        ];
    }

    /**
     * Règles de notification à programmer sur le téléphone, avec les dates que seul le serveur connaît
     * (prochain tirage Oracle, fin des défis rejoints). L'app évalue le reste (activités du jour, série, absence).
     *
     * @return array<string,mixed>
     */
    public function notificationPlan(User $user): array
    {
        $today = today();
        $rules = CompanionMessage::where('kind', CompanionMessage::KIND_NOTIFICATION)->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', $today))
            ->whereNotNull('send_time')
            ->orderByDesc('priority')->orderBy('id')
            ->get()
            ->map(fn (CompanionMessage $m) => [
                'id' => $m->id,
                'trigger' => $m->trigger,
                'activity' => $m->activity,
                'value' => $m->trigger_value,
                'time' => $m->send_time,
                'weekdays' => $m->weekdays ? array_map('intval', str_split($m->weekdays)) : [1, 2, 3, 4, 5, 6, 7],
                'title' => $m->title,
                'body' => $m->body,
                'action' => $m->action,
                'priority' => $m->priority,
                'starts_at' => $m->starts_at?->toDateString(),
                'ends_at' => $m->ends_at?->toDateString(),
            ])->values()->all();

        $nextOracle = null;
        try {
            $status = $this->oracle->status($user);
            if (($status['remaining_cards'] ?? 0) > 0) {
                $nextOracle = $status['can_draw'] ? now()->toIso8601String() : $status['next_draw_at'];
            }
        } catch (\Throwable) {
            // Oracle indisponible : pas de rappel Oracle.
        }

        $challenges = ChallengeParticipant::with('challenge')
            ->where('user_id', $user->id)->whereNull('completed_at')->get()
            ->filter(fn ($p) => $p->challenge && $p->challenge->ends_at && $p->challenge->ends_at->gte($today))
            ->map(fn ($p) => ['title' => $p->challenge->title, 'ends_at' => $p->challenge->ends_at->toDateString()])
            ->values()->all();

        return [
            'max_per_day' => CompanionMessage::maxPerDay(),
            'rules' => $rules,
            'oracle_next_draw_at' => $nextOracle,
            'challenges' => $challenges,
        ];
    }

    /** @return array<string,mixed> */
    private function facts(User $user, array $context, int $absentDays): array
    {
        $hour = (int) ($context['hour'] ?? now()->format('G'));
        $pending = $context['pending'] ?? [];
        $done = $context['done_today'] ?? [];

        $facts = [
            'welcome' => true,
            'morning' => $hour >= 5 && $hour <= 11,
            'afternoon' => $hour >= 12 && $hour <= 17,
            'evening' => $hour >= 18 || $hour <= 4,
            'absent_days' => $absentDays,
            'missed_yesterday' => !empty($context['missed_yesterday']),
            'all_done_today' => $pending === [] && $done !== [],
            'nothing_done_today' => $done === [],
            'oracle_available' => false,
            'challenge_days_left' => null,
            'challenge_title' => null,
        ];

        try {
            $facts['oracle_available'] = (bool) ($this->oracle->status($user)['can_draw'] ?? false);
        } catch (\Throwable) {
            // Oracle indisponible : on ignore simplement ce déclencheur.
        }

        $participation = ChallengeParticipant::with('challenge')
            ->where('user_id', $user->id)
            ->whereNull('completed_at')
            ->get()
            ->filter(fn ($p) => $p->challenge && $p->challenge->ends_at && $p->challenge->ends_at->gte(today()))
            ->sortBy(fn ($p) => $p->challenge->ends_at)
            ->first();
        if ($participation) {
            $facts['challenge_days_left'] = (int) today()->diffInDays($participation->challenge->ends_at);
            $facts['challenge_title'] = $participation->challenge->title;
        }

        return $facts;
    }

    /** Messages actifs dont le déclencheur est atteint et le délai écoulé, du plus prioritaire au moins prioritaire. */
    private function candidates(User $user, array $facts): Collection
    {
        $today = today();
        $messages = CompanionMessage::where('kind', CompanionMessage::KIND_CARD)->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhereDate('starts_at', '<=', $today))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', $today))
            ->orderByDesc('priority')->orderBy('id')
            ->get();

        $lastShown = CompanionMessageView::where('user_id', $user->id)
            ->whereIn('companion_message_id', $messages->pluck('id'))
            ->selectRaw('companion_message_id, max(shown_at) as last_shown')
            ->groupBy('companion_message_id')
            ->pluck('last_shown', 'companion_message_id');

        return $messages->filter(function (CompanionMessage $m) use ($facts, $lastShown) {
            if (!$this->matches($m, $facts)) {
                return false;
            }
            $last = $lastShown[$m->id] ?? null;
            return $last === null || $m->cooldown_hours === 0
                || now()->diffInHours(\Carbon\Carbon::parse($last), true) >= $m->cooldown_hours;
        })->values();
    }

    private function matches(CompanionMessage $m, array $facts): bool
    {
        $value = $m->trigger_value ?? 1;
        return match ($m->trigger) {
            'absent_days' => $facts['absent_days'] >= max(1, $value),
            'challenge_ending' => $facts['challenge_days_left'] !== null && $facts['challenge_days_left'] <= $value,
            default => (bool) ($facts[$m->trigger] ?? false),
        };
    }

    private function fill(string $text, array $replace): string
    {
        $text = strtr($text, $replace);
        // Prénom inconnu : « Bonjour . » devient « Bonjour. » et « , . » disparaît.
        return trim(preg_replace(['/\s+([.,!?])/u', '/,\s*\./u', '/\s{2,}/u'], ['$1', '.', ' '], $text));
    }
}
