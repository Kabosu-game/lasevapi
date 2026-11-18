<?php

namespace Tests\Feature;

use App\Models\CompanionMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Compagnon sans IA : rappels programmés et messages d'accueil, entièrement gérés depuis l'admin. */
class CompanionTest extends TestCase
{
    use RefreshDatabase;

    /** Même jeu de migrations que NewFeaturesTest (quelques anciennes migrations sont propres à MySQL). */
    protected function migrateFreshUsing()
    {
        $mysqlOnly = [
            '2026_01_13_100001_update_affirmations_table_add_category_id.php',
            '2026_01_12_152000_remove_user_id_from_food_comfort_forms_table.php',
            '2026_01_13_000000_add_user_id_back_to_food_comfort_forms_table.php',
        ];
        $paths = collect(glob(database_path('migrations/*.php')))
            ->reject(fn ($path) => in_array(basename($path), $mysqlOnly, true))
            ->values()->all();

        return ['--path' => $paths, '--realpath' => true];
    }

    private function token(): string
    {
        return $this->postJson('/api/auth/device', ['device_id' => Str::random(32), 'name' => 'Marie Dupont'])
            ->assertOk()->json('token');
    }

    private function api(string $token): static
    {
        $this->app['auth']->forgetGuards();
        return $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin-companion@x.fr', 'password' => 'secret', 'role' => 'admin', 'device_id' => 'admin-' . Str::random(10)]);
    }

    public function test_notification_plan_lists_active_rules_with_morning_afternoon_and_evening(): void
    {
        $token = $this->token();
        CompanionMessage::where('trigger', 'oracle_available')->update(['is_active' => false]);

        $plan = $this->api($token)->getJson('/api/companion/notifications')->assertOk()->json('data');

        $this->assertSame(CompanionMessage::MAX_PER_DAY_DEFAULT, $plan['max_per_day']);
        $times = collect($plan['rules'])->where('trigger', 'nothing_done_today')->pluck('time')->all();
        $this->assertEqualsCanonicalizing(['08:30', '14:30', '20:30'], $times);
        $this->assertNotContains('oracle_available', array_column($plan['rules'], 'trigger'));
        $meditation = collect($plan['rules'])->firstWhere('activity', 'meditation');
        $this->assertSame([1, 2, 3, 4, 5, 6, 7], $meditation['weekdays']);
        $this->assertSame('meditation', $meditation['action']);
    }

    public function test_notifications_require_authentication(): void
    {
        $this->getJson('/api/companion/notifications')->assertUnauthorized();
    }

    public function test_greeting_card_respects_cooldown_and_replaces_first_name(): void
    {
        $token = $this->token();

        $first = $this->api($token)->postJson('/api/assistant/greeting', ['first_name' => 'Awa', 'hour' => 19, 'pending' => ['Méditation']])
            ->assertOk()->json('data');
        $this->assertSame('evening', $first['messages'][0]['trigger']);
        $this->assertStringStartsWith('Bonsoir Awa.', $first['message']);

        // Le message du soir a un délai de 20 h : à la visite suivante, c'est l'accueil par défaut.
        $second = $this->api($token)->postJson('/api/assistant/greeting', ['first_name' => 'Awa', 'hour' => 20, 'pending' => ['Méditation']])
            ->assertOk()->json('data');
        $this->assertSame('welcome', $second['messages'][0]['trigger']);
        $this->assertStringContainsString('Awa', $second['message']);
    }

    public function test_admin_creates_edits_toggles_and_deletes_a_notification(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('admin.companion.index'))->assertOk()->assertSee('Notifications sur le téléphone');
        $this->get(route('admin.companion.index', ['kind' => 'card']))->assertOk();
        $this->get(route('admin.companion.create'))->assertOk();
        $this->get(route('admin.companion.create', ['kind' => 'card']))->assertOk();

        $this->post(route('admin.companion.store'), [
            'kind' => 'notification', 'trigger' => 'activity_not_done', 'activity' => 'yoga',
            'send_time' => '07:15', 'weekdays' => ['1', '3', '5'], 'title' => 'Yoga du matin',
            'body' => 'Salut {prenom}, on déroule le tapis ?', 'action' => 'fitness', 'priority' => 12, 'is_active' => '1',
        ])->assertRedirect();

        $message = CompanionMessage::where('title', 'Yoga du matin')->firstOrFail();
        $this->assertSame('135', $message->weekdays);
        $this->assertSame('yoga', $message->activity);
        $this->get(route('admin.companion.edit', $message))->assertOk()->assertSee('Yoga du matin');

        $this->put(route('admin.companion.update', $message), [
            'trigger' => 'nothing_done_today', 'activity' => 'yoga', 'send_time' => '16:00',
            'title' => 'Pause yoga', 'body' => 'Et si on bougeait ?', 'is_active' => '1',
        ])->assertRedirect();
        $message->refresh();
        $this->assertSame('16:00', $message->send_time);
        $this->assertNull($message->activity); // l'activité ne sert qu'à « activité non faite »
        $this->assertNull($message->weekdays); // aucun jour coché = tous les jours

        $this->post(route('admin.companion.toggle', $message))->assertRedirect();
        $this->assertFalse($message->fresh()->is_active);

        $this->post(route('admin.companion.settings'), ['max_per_day' => 3])->assertRedirect();
        $this->assertSame(3, CompanionMessage::maxPerDay());

        $this->delete(route('admin.companion.destroy', $message))->assertRedirect();
        $this->assertDatabaseMissing('companion_messages', ['id' => $message->id]);
    }

    public function test_notification_needs_a_time_and_activity_when_required(): void
    {
        $this->actingAs($this->admin());
        $this->post(route('admin.companion.store'), [
            'kind' => 'notification', 'trigger' => 'activity_not_done', 'title' => 'X', 'body' => 'Y',
        ])->assertSessionHasErrors(['send_time', 'activity']);
    }

    public function test_assistant_menu_tile_no_longer_mentions_ai(): void
    {
        $this->assertDatabaseHas('home_menu_items', ['slug' => 'assistant', 'name' => 'Mon compagnon']);
    }
}
