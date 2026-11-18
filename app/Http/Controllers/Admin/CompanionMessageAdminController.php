<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\CompanionMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * COMPAGNON : notifications de rappel (matin, après-midi, soir, activité non faite, série, absence…)
 * et messages affichés dans « Mon compagnon ». Tout se règle ici, sans nouvelle version de l'app.
 */
class CompanionMessageAdminController extends Controller
{
    public function index(Request $request)
    {
        $kind = $request->query('kind') === CompanionMessage::KIND_CARD ? CompanionMessage::KIND_CARD : CompanionMessage::KIND_NOTIFICATION;
        $messages = CompanionMessage::where('kind', $kind)
            ->withCount('views')
            ->orderByRaw('send_time is null')->orderBy('send_time')->orderByDesc('priority')->orderBy('id')
            ->get();

        return view('admin.companion.index', [
            'kind' => $kind,
            'messages' => $messages,
            'maxPerDay' => CompanionMessage::maxPerDay(),
        ]);
    }

    public function create(Request $request)
    {
        $kind = $request->query('kind') === CompanionMessage::KIND_CARD ? CompanionMessage::KIND_CARD : CompanionMessage::KIND_NOTIFICATION;
        return view('admin.companion.form', ['message' => new CompanionMessage([
            'kind' => $kind,
            'trigger' => $kind === CompanionMessage::KIND_CARD ? 'welcome' : 'nothing_done_today',
            'send_time' => $kind === CompanionMessage::KIND_CARD ? null : '09:00',
            'priority' => 10,
            'cooldown_hours' => 24,
            'is_active' => true,
        ])]);
    }

    public function store(Request $request)
    {
        $message = CompanionMessage::create($this->validated($request));
        return redirect()->route('admin.companion.index', ['kind' => $message->kind])->with('success', 'Message créé.');
    }

    public function edit(CompanionMessage $companion)
    {
        return view('admin.companion.form', ['message' => $companion]);
    }

    public function update(Request $request, CompanionMessage $companion)
    {
        $companion->update($this->validated($request, $companion));
        return redirect()->route('admin.companion.index', ['kind' => $companion->kind])->with('success', 'Message mis à jour.');
    }

    public function destroy(CompanionMessage $companion)
    {
        if ($companion->audio) {
            Storage::disk('public')->delete($companion->audio);
        }
        $kind = $companion->kind;
        $companion->delete();
        return redirect()->route('admin.companion.index', ['kind' => $kind])->with('success', 'Message supprimé.');
    }

    /** Active ou désactive un message sans passer par le formulaire. */
    public function toggle(CompanionMessage $companion)
    {
        $companion->update(['is_active' => !$companion->is_active]);
        return back()->with('success', $companion->is_active ? 'Message activé.' : 'Message désactivé.');
    }

    /** Nombre maximum de notifications envoyées par jour à un même utilisateur. */
    public function settings(Request $request)
    {
        $data = $request->validate(['max_per_day' => 'required|integer|min:1|max:10']);
        AppSetting::updateOrCreate(
            ['key' => CompanionMessage::MAX_PER_DAY_SETTING],
            [
                'value' => (string) $data['max_per_day'],
                'type' => 'text',
                'group' => 'general',
                'label' => 'Compagnon : notifications maximum par jour',
            ],
        );
        return back()->with('success', 'Réglage enregistré.');
    }

    private function validated(Request $request, ?CompanionMessage $message = null): array
    {
        $kind = $message?->kind ?? ($request->input('kind') === CompanionMessage::KIND_CARD ? CompanionMessage::KIND_CARD : CompanionMessage::KIND_NOTIFICATION);
        $isNotification = $kind === CompanionMessage::KIND_NOTIFICATION;
        $triggers = CompanionMessage::triggersFor($kind);
        $needsValue = ($triggers[$request->input('trigger')][1] ?? null) !== null;

        $data = $request->validate([
            'trigger' => ['required', Rule::in(array_keys($triggers))],
            'activity' => [Rule::requiredIf($request->input('trigger') === 'activity_not_done'), 'nullable', Rule::in(array_keys(CompanionMessage::ACTIVITIES))],
            'trigger_value' => [Rule::requiredIf($needsValue), 'nullable', 'integer', 'min:1', 'max:365'],
            'send_time' => [Rule::requiredIf($isNotification), 'nullable', 'date_format:H:i'],
            'weekdays' => 'nullable|array',
            'weekdays.*' => Rule::in(array_keys(CompanionMessage::WEEKDAYS)),
            'title' => [$isNotification ? 'required' : 'nullable', 'string', 'max:120'],
            'body' => 'required|string|max:500',
            'audio' => 'nullable|file|mimes:mp3,m4a,aac,wav,ogg|max:10240',
            'remove_audio' => 'nullable|boolean',
            'action' => ['nullable', Rule::in(array_keys(CompanionMessage::ACTIONS))],
            'action_label' => 'nullable|string|max:60',
            'priority' => 'nullable|integer|min:0|max:100',
            'cooldown_hours' => 'nullable|integer|min:0|max:720',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'nullable|boolean',
        ]);

        $days = collect($data['weekdays'] ?? [])->unique()->sort()->implode('');
        $clean = [
            'kind' => $kind,
            'trigger' => $data['trigger'],
            'activity' => $data['trigger'] === 'activity_not_done' ? $data['activity'] : null,
            'trigger_value' => $needsValue ? $data['trigger_value'] : null,
            'send_time' => $isNotification ? $data['send_time'] : null,
            'weekdays' => ($isNotification && $days !== '' && strlen($days) < 7) ? $days : null,
            'title' => $data['title'] ?? null,
            'body' => $data['body'],
            'action' => $data['action'] ?? null,
            'action_label' => $data['action_label'] ?? null,
            'priority' => $data['priority'] ?? 0,
            'cooldown_hours' => $data['cooldown_hours'] ?? 24,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];

        // Fichier audio : seulement pour les messages affichés dans l'app.
        if (!$isNotification) {
            if ($request->boolean('remove_audio') && $message?->audio) {
                Storage::disk('public')->delete($message->audio);
                $clean['audio'] = null;
            }
            if ($request->hasFile('audio')) {
                if ($message?->audio) {
                    Storage::disk('public')->delete($message->audio);
                }
                $clean['audio'] = $request->file('audio')->store('companion', 'public');
            }
        }

        return $clean;
    }
}
