<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Message du compagnon, écrit dans l'admin :
 *  - notification : rappel programmé sur le téléphone à l'heure choisie, si sa condition est remplie ;
 *  - card : message affiché en haut de « Mon compagnon » à l'ouverture.
 */
class CompanionMessage extends Model
{
    public const KIND_NOTIFICATION = 'notification';
    public const KIND_CARD = 'card';

    /** Réglage (AppSetting) : nombre maximum de notifications envoyées par jour. */
    public const MAX_PER_DAY_SETTING = 'companion_max_notifications_per_day';
    public const MAX_PER_DAY_DEFAULT = 4;

    protected $fillable = [
        'kind',
        'trigger',
        'activity',
        'trigger_value',
        'send_time',
        'weekdays',
        'title',
        'body',
        'audio',
        'action',
        'action_label',
        'priority',
        'cooldown_hours',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'trigger_value' => 'integer',
        'priority' => 'integer',
        'cooldown_hours' => 'integer',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Conditions d'envoi des notifications (clé => [libellé admin, sens de la valeur ou null]).
     * L'app les évalue au moment de programmer ; ajouter un nouveau type demande une mise à jour de l'app.
     */
    public const NOTIFICATION_TRIGGERS = [
        'daily' => ['Tous les jours, sans condition', null],
        'nothing_done_today' => ['Si aucune activité n\'a été faite dans la journée', null],
        'activity_not_done' => ['Si l\'activité choisie n\'a pas été faite dans la journée', null],
        'streak_at_risk' => ['Série d\'au moins N jours en danger (rien fait aujourd\'hui)', 'Nombre de jours de série'],
        'absent_days' => ['Si l\'app n\'a pas été ouverte depuis N jours', 'Nombre de jours'],
        'oracle_available' => ['Le jour où un tirage Oracle redevient disponible', null],
        'challenge_ending' => ['N jours avant la fin d\'un défi rejoint et non terminé', 'Nombre de jours'],
    ];

    /** Conditions des messages affichés dans « Mon compagnon ». */
    public const CARD_TRIGGERS = [
        'welcome' => ['Toujours (message par défaut)', null],
        'morning' => ['Le matin (5 h – 11 h)', null],
        'afternoon' => ['L\'après-midi (12 h – 17 h)', null],
        'evening' => ['Le soir (18 h – 4 h)', null],
        'absent_days' => ['Absent depuis au moins N jours', 'Nombre de jours'],
        'missed_yesterday' => ['Programme d\'hier non terminé', null],
        'all_done_today' => ['Programme du jour terminé', null],
        'nothing_done_today' => ['Rien fait aujourd\'hui', null],
        'challenge_ending' => ['Un défi rejoint se termine dans N jours ou moins', 'Nombre de jours'],
        'oracle_available' => ['Un tirage Oracle est disponible', null],
    ];

    /** Activités suivies par l'app (mêmes clés que le journal d'activité du téléphone). */
    public const ACTIVITIES = [
        'meditation' => 'Méditation',
        'listening' => 'Séance d\'écoute',
        'breathing' => 'Respiration',
        'yoga' => 'Yoga',
        'fitness' => 'Entraînement (Fitness)',
        'gratitude' => 'Gratitude',
        'mood' => 'Humeur du jour',
        'note' => 'Journal',
        'checkin' => 'Bilan du jour',
        'oracle' => 'Tirage Oracle',
        'introspection' => 'Face à toi (7 Portes)',
        'ritual' => 'Rituel',
    ];

    /** Écrans que le message peut ouvrir dans l'app (clé => libellé admin). */
    public const ACTIONS = [
        'meditation' => 'Méditation',
        'breathing' => 'Respiration',
        'activities' => 'Activités',
        'journal' => 'My Journal',
        'checkin' => 'Bilan du jour',
        'oracle' => 'Oracle',
        'challenges' => 'Défis du mois',
        'inner_gates' => 'Face à toi (7 Portes)',
        'fitness' => 'Fitness Center',
        'events' => 'Événements',
        'companion' => 'Mon compagnon',
    ];

    public const WEEKDAYS = ['1' => 'Lun', '2' => 'Mar', '3' => 'Mer', '4' => 'Jeu', '5' => 'Ven', '6' => 'Sam', '7' => 'Dim'];

    public static function triggersFor(string $kind): array
    {
        return $kind === self::KIND_CARD ? self::CARD_TRIGGERS : self::NOTIFICATION_TRIGGERS;
    }

    public static function maxPerDay(): int
    {
        return max(1, min(10, (int) AppSetting::getValue(self::MAX_PER_DAY_SETTING, self::MAX_PER_DAY_DEFAULT)));
    }

    public function views()
    {
        return $this->hasMany(CompanionMessageView::class);
    }

    public function audioUrl(): ?string
    {
        return $this->audio ? Storage::disk('public')->url($this->audio) : null;
    }

    public function triggerLabel(): string
    {
        $label = self::triggersFor($this->kind)[$this->trigger][0] ?? $this->trigger;
        if ($this->trigger_value !== null) {
            $label = str_replace(' N ', ' ' . $this->trigger_value . ' ', $label);
        }
        if ($this->trigger === 'activity_not_done' && $this->activity) {
            $label .= ' : ' . (self::ACTIVITIES[$this->activity] ?? $this->activity);
        }
        return $label;
    }

    public function weekdaysLabel(): string
    {
        if (!$this->weekdays || strlen($this->weekdays) === 7) {
            return 'Tous les jours';
        }
        return collect(str_split($this->weekdays))->map(fn ($d) => self::WEEKDAYS[$d] ?? $d)->implode(', ');
    }
}
