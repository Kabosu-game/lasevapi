<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\FitnessLesson;
use App\Models\FitnessProgram;
use App\Models\InnerGate;
use App\Models\InnerGateStep;
use App\Models\OracleCard;
use App\Models\OracleDraw;
use App\Models\Ritual;
use App\Models\RitualCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Nouvelle architecture fonctionnelle : Oracles, Défis, Fitness, Assistant, session mobile, admin. */
class NewFeaturesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Les tests tournent sur SQLite en mémoire (phpunit.xml). Quelques anciennes migrations
     * (suppression de colonnes indexées) ne sont compatibles qu'avec MySQL : on les écarte ici,
     * elles ne concernent pas les fonctionnalités testées.
     */
    protected function migrateFreshUsing()
    {
        $mysqlOnly = [
            '2026_01_13_100001_update_affirmations_table_add_category_id.php',
            '2026_01_12_152000_remove_user_id_from_food_comfort_forms_table.php',
            '2026_01_13_000000_add_user_id_back_to_food_comfort_forms_table.php',
        ];
        $paths = collect(glob(database_path('migrations/*.php')))
            ->reject(fn ($path) => in_array(basename($path), $mysqlOnly, true))
            ->values()
            ->all();

        return ['--path' => $paths, '--realpath' => true];
    }

    private function deviceToken(string $deviceId = null, string $name = 'Marie Dupont'): array
    {
        $deviceId ??= Str::random(32);
        $res = $this->postJson('/api/auth/device', ['device_id' => $deviceId, 'name' => $name])->assertOk();
        return [$res->json('token'), $deviceId, $res->json('user.id')];
    }

    private function api(string $token): static
    {
        // Le garde d'authentification garde l'utilisateur en mémoire entre deux requêtes d'un même test.
        $this->app['auth']->forgetGuards();
        return $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    public function test_device_session_returns_same_account_for_same_device(): void
    {
        [$token, $device, $id] = $this->deviceToken();
        [, , $id2] = $this->deviceToken($device);
        $this->assertSame($id, $id2);
        $this->assertNotEmpty($token);
        $this->assertSame(1, User::where('device_id', $device)->count());
    }

    public function test_device_session_refuses_admin_account(): void
    {
        User::create(['name' => 'Admin', 'email' => 'a@x.fr', 'password' => 'x', 'role' => 'admin', 'device_id' => 'admin-device-id-123456']);
        $this->postJson('/api/auth/device', ['device_id' => 'admin-device-id-123456'])->assertForbidden();
    }

    public function test_transfer_code_moves_account_to_new_phone(): void
    {
        [$token, , $id] = $this->deviceToken();
        $code = $this->api($token)->getJson('/api/auth/transfer-code')->assertOk()->json('code');

        $newDevice = Str::random(32);
        $res = $this->postJson('/api/auth/transfer', ['code' => strtolower($code), 'device_id' => $newDevice])->assertOk();
        $this->assertSame($id, $res->json('user.id'));
        $this->assertSame($newDevice, User::find($id)->device_id);
        // Le code est à usage unique.
        $this->postJson('/api/auth/transfer', ['code' => $code, 'device_id' => Str::random(32)])->assertStatus(422);
    }

    public function test_oracle_once_every_three_days(): void
    {
        $this->travelTo(now()->startOfMonth()->setTime(9, 0));
        [$token] = $this->deviceToken();

        $this->api($token)->getJson('/api/oracle/status')->assertOk()->assertJsonPath('data.can_draw', true);
        $draw = $this->api($token)->postJson('/api/oracle/draw', ['goals' => ['Gérer mon stress']])->assertCreated();
        $this->assertNotEmpty($draw->json('data.draw.personalized_interpretation'));
        $this->assertStringContainsString('Marie', $draw->json('data.draw.personalized_interpretation'));

        $this->api($token)->postJson('/api/oracle/draw')->assertStatus(409)->assertJsonPath('data.can_draw', false);
        $this->travel(2)->days();
        $this->api($token)->postJson('/api/oracle/draw')->assertStatus(409);
        $this->travel(1)->days();
        $this->api($token)->postJson('/api/oracle/draw')->assertCreated();
    }

    public function test_oracle_never_repeats_a_card_in_the_same_month(): void
    {
        $this->travelTo(now()->startOfMonth()->setTime(8, 0));
        [$token, , $userId] = $this->deviceToken();

        // Paquet réduit à 3 cartes pour atteindre l'épuisement.
        OracleCard::query()->update(['is_active' => false]);
        OracleCard::limit(3)->update(['is_active' => true]);

        for ($i = 0; $i < 3; $i++) {
            $this->api($token)->postJson('/api/oracle/draw')->assertCreated();
            $this->travel(3)->days();
        }
        $cards = OracleDraw::where('user_id', $userId)->pluck('oracle_card_id');
        $this->assertCount(3, $cards->unique());

        $this->api($token)->postJson('/api/oracle/draw')->assertStatus(409)
            ->assertJsonPath('data.remaining_cards', 0);

        // Nouveau cycle le mois suivant : les cartes redeviennent disponibles.
        $this->travelTo(now()->addMonthNoOverflow()->startOfMonth()->setTime(8, 0));
        $this->api($token)->postJson('/api/oracle/draw')->assertCreated();
        $this->api($token)->getJson('/api/oracle/history')->assertOk()->assertJsonCount(4, 'data');
    }

    public function test_challenges_join_checkin_points_and_leaderboard(): void
    {
        [$token] = $this->deviceToken();
        [$token2] = $this->deviceToken(null, 'Paul Martin');

        $list = $this->api($token)->getJson('/api/challenges')->assertOk();
        $this->assertCount(6, $list->json('data'));
        $challenge = Challenge::where('type', 'meditation')->firstOrFail();

        $this->api($token)->postJson("/api/challenges/{$challenge->id}/join")->assertOk()->assertJsonPath('data.me.joined', true);
        $this->api($token)->postJson("/api/challenges/{$challenge->id}/checkin")->assertOk()
            ->assertJsonPath('data.me.days_completed', 1)
            ->assertJsonPath('data.me.points', 10)
            ->assertJsonPath('data.me.checked_in_today', true);
        $this->api($token)->postJson("/api/challenges/{$challenge->id}/checkin")->assertStatus(409);

        $this->api($token2)->postJson("/api/challenges/{$challenge->id}/checkin")->assertOk()->assertJsonPath('data.my_rank', 1); // ex æquo : mêmes points, mêmes jours

        $show = $this->api($token2)->getJson("/api/challenges/{$challenge->id}")->assertOk();
        $this->assertCount(2, $show->json('data.leaderboard'));
        // Classement public : prénom + initiale seulement.
        $this->assertEqualsCanonicalizing(['Marie D.', 'Paul M.'], collect($show->json('data.leaderboard'))->pluck('name')->all());
        $this->assertSame(1, collect($show->json('data.leaderboard'))->where('is_me', true)->count());
        $this->assertSame([1, 1], collect($show->json('data.leaderboard'))->pluck('rank')->all());
    }

    public function test_challenge_completion_awards_bonus_and_badge(): void
    {
        [$token] = $this->deviceToken();
        $challenge = Challenge::create([
            'title' => 'Mini défi', 'objective' => 'Tester', 'type' => 'other', 'target_days' => 2,
            'points_per_day' => 10, 'completion_bonus' => 50, 'badge_name' => 'Testeur',
            'starts_at' => now()->subDay()->toDateString(), 'ends_at' => now()->addDays(5)->toDateString(),
        ]);
        $this->api($token)->postJson("/api/challenges/{$challenge->id}/checkin")->assertOk();
        $this->travel(1)->days();
        $this->api($token)->postJson("/api/challenges/{$challenge->id}/checkin")->assertOk()
            ->assertJsonPath('data.me.completed', true)
            ->assertJsonPath('data.me.points', 70);
        $this->travel(1)->days();
        $this->api($token)->postJson("/api/challenges/{$challenge->id}/checkin")->assertStatus(409);

        $summary = $this->api($token)->getJson('/api/challenges-summary')->assertOk();
        $this->assertSame(70, $summary->json('data.total_points'));
        $this->assertSame('Testeur', $summary->json('data.badges.0.name'));
    }

    public function test_recurring_challenges_roll_over_to_next_month(): void
    {
        [$token] = $this->deviceToken();
        $challenge = Challenge::where('type', 'yoga')->firstOrFail();
        $this->api($token)->postJson("/api/challenges/{$challenge->id}/checkin")->assertOk();

        $this->travelTo(now()->addMonthNoOverflow()->startOfMonth()->addDays(2));
        $list = $this->api($token)->getJson('/api/challenges')->assertOk();

        $this->assertNotNull($challenge->fresh()->closed_at);
        $this->assertSame(1, $challenge->participants()->first()->final_rank);
        $next = Challenge::where('parent_challenge_id', $challenge->id)->firstOrFail();
        $this->assertSame(now()->startOfMonth()->toDateString(), $next->starts_at->toDateString());
        $this->assertContains($next->id, collect($list->json('data'))->pluck('id')->all());
        $this->assertCount(6, $list->json('data'));
    }

    public function test_fitness_programs_sessions_and_stats(): void
    {
        [$token] = $this->deviceToken();
        $program = FitnessProgram::create(['title' => 'Yoga débutant', 'category' => 'yoga', 'level' => 'debutant', 'duration_days' => 7]);
        $lesson = FitnessLesson::create(['fitness_program_id' => $program->id, 'day_number' => 1, 'title' => 'Salutation au soleil', 'video_url' => 'https://example.com/v.mp4', 'duration_minutes' => 15]);
        FitnessProgram::create(['title' => 'Caché', 'category' => 'training', 'level' => 'avance', 'duration_days' => 30, 'is_published' => false]);

        $this->api($token)->getJson('/api/fitness/programs?category=yoga')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.completed_lessons', 0);
        $this->api($token)->postJson('/api/fitness/sessions', ['lesson_id' => $lesson->id])->assertCreated()
            ->assertJsonPath('stats.total_sessions', 1)
            ->assertJsonPath('stats.total_minutes', 15)
            ->assertJsonPath('stats.current_streak', 1);
        $this->api($token)->getJson("/api/fitness/programs/{$program->id}")->assertOk()
            ->assertJsonPath('data.lessons.0.completed', true)
            ->assertJsonPath('data.lessons.0.video', 'https://example.com/v.mp4');
    }

    public function test_companion_greeting_and_checkin_use_written_messages_only(): void
    {
        [$token] = $this->deviceToken();

        // Matin, rien fait : message du matin (priorité 10) plutôt que l'accueil par défaut.
        $res = $this->api($token)->postJson('/api/assistant/greeting', ['first_name' => 'Marie', 'pending' => ['Méditation'], 'hour' => 8])
            ->assertOk()->assertJsonPath('ai', false);
        $this->assertStringContainsString('Bonjour Marie', $res->json('data.message'));
        $this->assertSame('morning', $res->json('data.messages.0.trigger'));
        $this->assertSame('breathing', $res->json('data.messages.0.action'));

        $res = $this->api($token)->postJson('/api/assistant/checkin', [
            'mood' => 3, 'energy' => 1, 'sleep' => 2, 'emotion' => 'fatigué', 'stress' => 4, 'wants_activity' => false,
        ])->assertOk()->assertJsonPath('ai', false);
        $this->assertStringContainsString('très faible', $res->json('data.summary'));
        $this->assertSame('breathing', $res->json('data.recommendations.0.type'));

        // La discussion libre avec une IA n'existe plus.
        $status = $this->api($token)->postJson('/api/assistant/chat', ['messages' => [['role' => 'user', 'content' => 'Bonjour']]])->status();
        $this->assertContains($status, [404, 405]);
    }

    public function test_new_endpoints_require_authentication(): void
    {
        $this->getJson('/api/oracle/status')->assertUnauthorized();
        $this->getJson('/api/challenges')->assertUnauthorized();
        $this->postJson('/api/assistant/greeting', [])->assertUnauthorized();
        $this->getJson('/api/inner-gates')->assertUnauthorized();
    }

    public function test_inner_gates_open_one_after_another(): void
    {
        [$token] = $this->deviceToken();

        $overview = $this->api($token)->getJson('/api/inner-gates')->assertOk();
        $this->assertCount(7, $overview->json('data.gates'));
        $this->assertSame(['available', 'locked'], [$overview->json('data.gates.0.status'), $overview->json('data.gates.1.status')]);
        $this->assertSame(1, $overview->json('data.summary.current_gate'));
        $this->assertSame(0, $overview->json('data.summary.completed_gates'));

        $gate1 = InnerGate::where('number', 1)->firstOrFail();
        $gate2 = InnerGate::where('number', 2)->firstOrFail();
        $this->api($token)->getJson("/api/inner-gates/{$gate2->id}")->assertStatus(409);

        $steps = $gate1->steps()->get();
        $this->assertCount(4, $steps);
        $this->api($token)->getJson("/api/inner-gates/{$gate1->id}")->assertOk()
            ->assertJsonPath('data.steps.0.available', true)
            ->assertJsonPath('data.steps.1.available', false)
            ->assertJsonPath('data.steps.2.prompts.0', fn ($q) => str_contains($q, 'sécurité'));
        // Les étapes se font dans l'ordre.
        $this->api($token)->postJson("/api/inner-gates/steps/{$steps[1]->id}/complete")->assertStatus(409);

        foreach ($steps as $step) {
            $res = $this->api($token)->postJson("/api/inner-gates/steps/{$step->id}/complete")->assertOk();
        }
        $res->assertJsonPath('data.gate_completed', true)
            ->assertJsonPath('data.gate.status', 'completed')
            ->assertJsonPath('data.next_gate.number', 2)
            ->assertJsonPath('data.summary.completed_gates', 1)
            ->assertJsonPath('data.summary.current_gate', 2)
            ->assertJsonPath('data.summary.progress', 0.14);

        // Valider à nouveau une étape ne change rien.
        $this->api($token)->postJson("/api/inner-gates/steps/{$steps[0]->id}/complete")->assertOk()
            ->assertJsonPath('data.gate_completed', false);

        $this->api($token)->getJson("/api/inner-gates/{$gate2->id}")->assertOk()
            ->assertJsonPath('data.status', 'available')
            ->assertJsonPath('data.steps.0.available', true)
            ->assertJsonPath('data.steps.1.available', false);

        // Une étape ajoutée ensuite ne referme pas une porte franchie.
        InnerGateStep::create(['inner_gate_id' => $gate1->id, 'position' => 9, 'type' => 'lesson', 'title' => 'Nouvelle étape']);
        $overview = $this->api($token)->getJson('/api/inner-gates')->assertOk();
        $this->assertSame(['completed', 'available'], [$overview->json('data.gates.0.status'), $overview->json('data.gates.1.status')]);

        // La progression est propre à chaque utilisateur.
        [$other] = $this->deviceToken(null, 'Paul Martin');
        $this->api($other)->getJson('/api/inner-gates')->assertOk()->assertJsonPath('data.gates.1.status', 'locked');
    }

    public function test_hidden_or_empty_gate_does_not_block_the_next_one(): void
    {
        [$token] = $this->deviceToken();

        InnerGate::where('number', 1)->update(['is_active' => false]);
        $this->api($token)->getJson('/api/inner-gates')->assertOk()
            ->assertJsonCount(6, 'data.gates')
            ->assertJsonPath('data.gates.0.number', 2)
            ->assertJsonPath('data.gates.0.status', 'available');

        InnerGateStep::where('inner_gate_id', InnerGate::where('number', 2)->value('id'))->delete();
        $this->api($token)->getJson('/api/inner-gates')->assertOk()
            ->assertJsonPath('data.gates.0.status', 'empty')
            ->assertJsonPath('data.gates.1.status', 'available')
            ->assertJsonPath('data.summary.current_gate', 3);
    }

    public function test_sacred_space_library(): void
    {
        $plants = RitualCategory::where('slug', 'plantes')->firstOrFail();
        $published = Ritual::create([
            'ritual_category_id' => $plants->id, 'title' => 'Bain de plantes', 'summary' => 'Se ressourcer',
            'objectives' => ['Se ressourcer'], 'materials' => ['Bassine'], 'steps' => [['title' => 'Préparer', 'text' => 'Remplir la bassine']],
            'precautions' => ['Tester sur une petite zone de peau'], 'traditions' => 'Tradition orale', 'is_published' => true,
        ]);
        $draft = Ritual::create(['ritual_category_id' => $plants->id, 'title' => 'Brouillon', 'is_published' => false]);

        $this->getJson('/api/rituals/categories')->assertOk()->assertJsonCount(7, 'data')
            ->assertJsonPath('data.2.slug', 'plantes')
            ->assertJsonPath('data.2.rituals_count', 1);
        $this->getJson('/api/rituals?category=plantes')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category.slug', 'plantes')
            ->assertJsonMissingPath('data.0.steps');
        $this->getJson('/api/rituals?q=plantes')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/rituals?category=prieres')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/rituals/{$published->id}")->assertOk()
            ->assertJsonPath('data.steps.0.title', 'Préparer')
            ->assertJsonPath('data.precautions.0', 'Tester sur une petite zone de peau');
        $this->getJson("/api/rituals/{$draft->id}")->assertNotFound();

        $plants->update(['is_active' => false]);
        $this->getJson("/api/rituals/{$published->id}")->assertNotFound();
        $this->getJson('/api/rituals')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_admin_inner_gates_and_sacred_space(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'admin2@x.fr', 'password' => 'secret', 'role' => 'admin', 'device_id' => 'admin-' . Str::random(10)]);
        $gate = InnerGate::where('number', 1)->firstOrFail();
        $step = $gate->steps()->firstOrFail();
        $category = RitualCategory::where('slug', 'purification')->firstOrFail();

        $this->actingAs($admin);
        foreach ([
            route('admin.inner-gates.index'), route('admin.inner-gates.show', $gate), route('admin.inner-gates.edit', $gate),
            route('admin.inner-gates.steps.create', $gate), route('admin.inner-gates.steps.edit', [$gate, $step]),
            route('admin.ritual-categories.index'), route('admin.ritual-categories.create'), route('admin.ritual-categories.edit', $category),
            route('admin.rituals.index'), route('admin.rituals.create'),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        // Porte : textes modifiables.
        $this->put(route('admin.inner-gates.update', $gate), [
            'name' => 'Chakra Racine', 'theme' => 'Mes racines', 'color' => '#AA0000', 'is_active' => '1',
        ])->assertRedirect(route('admin.inner-gates.show', $gate));
        $this->assertSame('Mes racines', $gate->fresh()->theme);

        // Étape d'introspection : une question par ligne, au moins une question.
        $this->post(route('admin.inner-gates.steps.store', $gate), [
            'position' => 5, 'type' => 'introspection', 'title' => 'Bilan', 'prompts_text' => "Question 1\n\n  Question 2  ",
        ])->assertRedirect(route('admin.inner-gates.show', $gate));
        $this->assertSame(['Question 1', 'Question 2'], InnerGateStep::where('title', 'Bilan')->firstOrFail()->prompts);
        $this->post(route('admin.inner-gates.steps.store', $gate), ['position' => 6, 'type' => 'introspection', 'title' => 'Vide'])
            ->assertSessionHasErrors('prompts_text');

        // Tutoriel : la publication exige les cinq rubriques.
        $this->post(route('admin.rituals.store'), ['ritual_category_id' => $category->id, 'title' => 'Incomplet', 'is_published' => '1'])
            ->assertSessionHasErrors(['objectives_text', 'materials_text', 'steps', 'precautions_text', 'traditions']);
        $this->post(route('admin.rituals.store'), ['ritual_category_id' => $category->id, 'title' => 'Brouillon libre'])
            ->assertRedirect(route('admin.rituals.index'));
        $this->post(route('admin.rituals.store'), [
            'ritual_category_id' => $category->id, 'title' => 'Purifier son espace', 'is_published' => '1',
            'objectives_text' => "Apaiser le lieu\nPoser une intention", 'materials_text' => 'Un bol d\'eau',
            'steps' => ['0' => ['title' => 'Aérer', 'text' => 'Ouvrir les fenêtres'], 'n1' => ['title' => '', 'text' => '']],
            'precautions_text' => 'Ne jamais laisser une flamme sans surveillance', 'traditions' => 'Pratique commune à de nombreuses traditions.',
        ])->assertRedirect(route('admin.rituals.index'));
        $ritual = Ritual::where('title', 'Purifier son espace')->firstOrFail();
        $this->assertSame([['title' => 'Aérer', 'text' => 'Ouvrir les fenêtres']], $ritual->steps);
        $this->assertSame(['Apaiser le lieu', 'Poser une intention'], $ritual->objectives);
        $this->get(route('admin.rituals.edit', $ritual))->assertOk();

        // Une catégorie qui contient des tutoriels ne peut pas être supprimée.
        $this->delete(route('admin.ritual-categories.destroy', $category))->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('ritual_categories', ['id' => $category->id]);
        $this->post(route('admin.ritual-categories.store'), ['name' => 'Chants sacrés', 'icon' => 'spa', 'is_active' => '1'])
            ->assertRedirect(route('admin.ritual-categories.index'));
        $this->assertDatabaseHas('ritual_categories', ['slug' => 'chants-sacres']);
    }

    public function test_meditations_filter_by_category(): void
    {
        \App\Models\Meditation::create(['title' => 'Course matinale', 'category' => 'jogging']);
        \App\Models\Meditation::create(['title' => 'Calme', 'category' => 'meditation']);
        $this->getJson('/api/meditations?category=jogging')->assertOk()->assertJsonCount(1)->assertJsonPath('0.category', 'jogging');
    }

    public function test_admin_pages_render(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@x.fr', 'password' => 'secret', 'role' => 'admin', 'device_id' => 'admin-' . Str::random(10)]);
        $program = FitnessProgram::create(['title' => 'Yoga', 'category' => 'yoga', 'level' => 'debutant', 'duration_days' => 7]);
        $lesson = FitnessLesson::create(['fitness_program_id' => $program->id, 'day_number' => 1, 'title' => 'Leçon 1']);
        $challenge = Challenge::firstOrFail();
        [$token] = $this->deviceToken();
        $this->api($token)->postJson("/api/challenges/{$challenge->id}/checkin")->assertOk();
        $card = OracleCard::firstOrFail();

        $this->actingAs($admin);
        foreach ([
            route('admin.oracle-cards.index'), route('admin.oracle-cards.create'), route('admin.oracle-cards.edit', $card),
            route('admin.challenges.index'), route('admin.challenges.create'), route('admin.challenges.show', $challenge), route('admin.challenges.edit', $challenge),
            route('admin.fitness-programs.index'), route('admin.fitness-programs.create'), route('admin.fitness-programs.show', $program),
            route('admin.fitness-programs.edit', $program), route('admin.fitness-programs.lessons.create', $program),
            route('admin.fitness-programs.lessons.edit', [$program, $lesson]), route('admin.meditations.create'),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        // Clôture manuelle et attribution de récompense.
        $participant = $challenge->participants()->firstOrFail();
        $this->post(route('admin.challenges.reward', [$challenge, $participant]))->assertRedirect();
        $this->assertTrue($participant->fresh()->reward_granted);
        $this->post(route('admin.challenges.close', $challenge))->assertRedirect();
        $this->assertNotNull($challenge->fresh()->closed_at);

        // Création d'un défi et d'une carte depuis l'admin.
        $this->post(route('admin.challenges.store'), [
            'title' => 'Défi test', 'objective' => 'Tester', 'type' => 'discipline', 'target_days' => 10,
            'points_per_day' => 5, 'completion_bonus' => 20, 'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(20)->toDateString(), 'is_published' => '1',
        ])->assertRedirect(route('admin.challenges.index'));
        $this->post(route('admin.oracle-cards.store'), [
            'name' => 'Nouvelle', 'message' => 'Msg', 'interpretation' => 'Sens', 'is_active' => '1',
        ])->assertRedirect(route('admin.oracle-cards.index'));
        $this->assertDatabaseHas('oracle_cards', ['name' => 'Nouvelle', 'is_active' => true]);
    }

    public function test_app_content_comes_from_the_api(): void
    {
        // Catégories Activités : ordre de l'admin, catégories masquées absentes.
        \App\Models\ActivityCategory::where('key', 'jogging')->update(['is_active' => false]);
        $categories = $this->getJson('/api/activity-categories')->assertOk()->json('data');
        $this->assertSame(['meditation', 'relaxation', 'sommeil', 'concentration', 'respiration'], array_column($categories, 'key'));
        $respiration = collect($categories)->firstWhere('key', 'respiration');
        $this->assertTrue($respiration['with_breathing_exercises']);
        $this->assertSame('air', $respiration['icon']);
        $this->assertMatchesRegularExpression('/^#[0-9A-F]{6}$/', $respiration['color']);

        // Exercices de respiration.
        $patterns = $this->getJson('/api/breathing-patterns')->assertOk()->json('data');
        $this->assertCount(4, $patterns);
        $this->assertSame(['Cohérence cardiaque', 5, 0, 5, 0], [
            $patterns[0]['name'], $patterns[0]['inhale_seconds'], $patterns[0]['hold_in_seconds'],
            $patterns[0]['exhale_seconds'], $patterns[0]['hold_out_seconds'],
        ]);

        // Objectifs de l'inscription : accents corrigés, ordre défini.
        $objectives = array_column($this->getJson('/api/objectives')->assertOk()->json(), 'name');
        $this->assertSame('Paix intérieure', $objectives[0]);
        $this->assertContains('Éveil spirituel', $objectives);
        $this->assertNotContains('Paix interieur', $objectives);

        // Accueil : ordre et visibilité gérés dans l'admin.
        $menu = collect($this->getJson('/api/home-menu-items')->assertOk()->json());
        $this->assertSame(
            ['assistant', 'oracles', 'activities', 'fitness', 'journal', 'challenges', 'inner-gates', 'sacred-space', 'articles', 'events', 'retreats', 'cuisine'],
            $menu->where('is_visible', true)->sortBy('sort_order')->pluck('slug')->values()->all()
        );

        // Textes de l'app dans le CMS.
        $content = $this->getJson('/api/settings/group/content')->assertOk()->json('data');
        foreach (['inner_gates_tagline', 'inner_gates_about', 'inner_gates_safety_note', 'oracle_disclaimer', 'sacred_space_intro', 'sacred_space_caution', 'privacy_policy'] as $key) {
            $this->assertNotEmpty($content[$key]['value'] ?? null, $key);
        }
        $general = $this->getJson('/api/settings/group/general')->assertOk()->json('data');
        $this->assertNotEmpty($general['app_website_url']['value']);
        $this->assertStringEndsWith('/politique-confidentialite.html', $general['privacy_policy_url']['value']);
    }

    public function test_daily_quotes_come_from_the_admin(): void
    {
        // Les anciennes phrases par défaut sont devenues une entrée modifiable dans l'admin.
        $quotes = $this->getJson('/api/daily-quote/today?date=' . now()->toDateString())->assertOk()->json('data.quotes');
        $this->assertCount(3, $quotes);
        $this->assertDatabaseCount('daily_quotes', 1);

        \App\Models\DailyQuote::query()->update(['quote_1' => 'Phrase de l\'admin']);
        $this->assertSame('Phrase de l\'admin', $this->getJson('/api/daily-quote/today?date=' . now()->toDateString())->json('data.quotes.0'));

        // Plus aucune phrase programmée : rien d'inventé.
        \App\Models\DailyQuote::query()->delete();
        $this->getJson('/api/daily-quote/today')->assertOk()->assertJsonPath('data.quotes', []);
        $this->getJson('/api/daily-quote')->assertOk()->assertJsonPath('data.quote', null);
    }

    public function test_event_registration_from_the_app(): void
    {
        $event = \App\Models\Event::create([
            'title' => 'Atelier respiration', 'event_date' => now()->addWeek(), 'location' => 'Centre',
            'duration' => '2 heures', 'contact' => '+229 00 00 00', 'price' => 0, 'status' => 'upcoming',
        ]);
        $this->getJson("/api/events/{$event->id}")->assertOk()
            ->assertJsonPath('duration', '2 heures')->assertJsonPath('contact', '+229 00 00 00');

        $payload = ['first_name' => 'Awa', 'last_name' => 'Kossou', 'phone' => '+229 97 00 00 00'];
        $this->postJson("/api/events/{$event->id}/register", $payload)->assertCreated()
            ->assertJsonPath('already_registered', false)->assertJsonPath('participants', 1);
        // Même téléphone : pas de doublon.
        $this->postJson("/api/events/{$event->id}/register", $payload)->assertOk()->assertJsonPath('already_registered', true);
        $this->assertDatabaseCount('event_registrations', 1);
        $this->assertSame(1, $event->fresh()->current_participants);

        $this->postJson("/api/events/{$event->id}/register", ['first_name' => 'A', 'last_name' => 'B', 'phone' => 'abc'])
            ->assertStatus(422)->assertJsonValidationErrors('phone');
        $event->update(['status' => 'completed']);
        $this->postJson("/api/events/{$event->id}/register", ['first_name' => 'Jean', 'last_name' => 'D', 'phone' => '0102030405'])
            ->assertStatus(409);

        // L'admin voit les inscrits.
        $admin = User::create(['name' => 'Admin', 'email' => 'admin4@x.fr', 'password' => 'secret', 'role' => 'admin', 'device_id' => 'admin-' . Str::random(10)]);
        $this->actingAs($admin)->get(route('admin.events.show', $event))->assertOk()->assertSee('Awa Kossou')->assertSee('2 heures');
    }

    public function test_assistant_only_recommends_visible_activity_categories(): void
    {
        \App\Models\ActivityCategory::where('key', 'respiration')->update(['is_active' => false]);
        [$token] = $this->deviceToken();
        $recs = $this->api($token)->postJson('/api/assistant/checkin', [
            'mood' => 3, 'energy' => 1, 'sleep' => 3, 'emotion' => 'fatigue', 'stress' => 5, 'wants_activity' => false,
        ])->assertOk()->json('data.recommendations');
        $this->assertNotEmpty($recs);
        $this->assertNotContains('respiration', array_column($recs, 'category'));
    }

    public function test_admin_manages_app_lists(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'admin3@x.fr', 'password' => 'secret', 'role' => 'admin', 'device_id' => 'admin-' . Str::random(10)]);
        $this->actingAs($admin);
        $category = \App\Models\ActivityCategory::where('key', 'jogging')->firstOrFail();
        $pattern = \App\Models\BreathingPattern::firstOrFail();
        $objective = \App\Models\Objective::firstOrFail();
        $menuItem = \App\Models\HomeMenuItem::where('slug', 'cuisine')->firstOrFail();

        foreach ([
            route('admin.activity-categories.index'), route('admin.activity-categories.create'), route('admin.activity-categories.edit', $category),
            route('admin.breathing-patterns.index'), route('admin.breathing-patterns.create'), route('admin.breathing-patterns.edit', $pattern),
            route('admin.objectives.index'), route('admin.objectives.create'), route('admin.objectives.edit', $objective),
            route('admin.home-menu-items.index'), route('admin.home-menu-items.edit', $menuItem->id), route('admin.cms.index'),
            route('admin.meditations.index'), route('admin.meditations.create'),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        // Nouvelle catégorie : clé générée, utilisable pour ranger un contenu.
        $this->post(route('admin.activity-categories.store'), [
            'name' => 'Yoga Nidra', 'description' => 'Relaxation profonde.', 'icon' => 'spa', 'color' => '#336699', 'is_active' => '1',
        ])->assertRedirect(route('admin.activity-categories.index'));
        $this->assertDatabaseHas('activity_categories', ['key' => 'yoga-nidra', 'color' => '#336699']);
        $this->post(route('admin.activity-categories.store'), ['name' => 'X', 'icon' => 'inconnue', 'color' => 'rouge'])
            ->assertSessionHasErrors(['icon', 'color']);

        // La clé ne change pas à la modification ; une catégorie utilisée ne peut pas être supprimée.
        $this->put(route('admin.activity-categories.update', $category), [
            'key' => 'autre', 'name' => 'Course', 'icon' => 'directions_run', 'color' => '#D9822B',
        ])->assertRedirect(route('admin.activity-categories.index'));
        $this->assertDatabaseHas('activity_categories', ['id' => $category->id, 'key' => 'jogging', 'name' => 'Course', 'is_active' => false]);
        \App\Models\Meditation::create(['title' => 'Course matinale', 'category' => 'jogging']);
        $this->delete(route('admin.activity-categories.destroy', $category))->assertSessionHas('error');
        $this->assertDatabaseHas('activity_categories', ['id' => $category->id]);

        // Exercice de respiration.
        $this->post(route('admin.breathing-patterns.store'), [
            'name' => 'Souffle 4-2-6', 'inhale_seconds' => 4, 'hold_in_seconds' => 2, 'exhale_seconds' => 6, 'is_active' => '1',
        ])->assertRedirect(route('admin.breathing-patterns.index'));
        $this->assertDatabaseHas('breathing_patterns', ['name' => 'Souffle 4-2-6', 'hold_out_seconds' => 0]);
        $this->post(route('admin.breathing-patterns.store'), ['name' => 'Vide', 'inhale_seconds' => 0, 'exhale_seconds' => 40])
            ->assertSessionHasErrors(['inhale_seconds', 'exhale_seconds']);

        // Objectifs : création, doublon refusé, suppression.
        $this->post(route('admin.objectives.store'), ['name' => 'Gratitude', 'sort_order' => 11])->assertRedirect(route('admin.objectives.index'));
        $this->post(route('admin.objectives.store'), ['name' => 'Gratitude'])->assertSessionHasErrors('name');
        $this->delete(route('admin.objectives.destroy', $objective))->assertRedirect(route('admin.objectives.index'));
        $this->assertDatabaseMissing('objectives', ['id' => $objective->id]);

        // Rubrique de l'accueil : ordre et visibilité ; une ancienne rubrique reste masquée.
        $this->put(route('admin.home-menu-items.update', $menuItem->id), ['name' => 'Recettes', 'sort_order' => 3, 'is_visible' => '0'])
            ->assertRedirect(route('admin.home-menu-items.index'));
        $this->assertDatabaseHas('home_menu_items', ['id' => $menuItem->id, 'name' => 'Recettes', 'sort_order' => 3, 'is_visible' => false]);
        $legacy = \App\Models\HomeMenuItem::create(['slug' => 'gratitude', 'name' => 'Gratitude', 'sort_order' => 92, 'is_visible' => false]);
        $this->put(route('admin.home-menu-items.update', $legacy->id), ['name' => 'Gratitude', 'is_visible' => '1'])->assertRedirect();
        $this->assertFalse($legacy->fresh()->is_visible);
    }
}
