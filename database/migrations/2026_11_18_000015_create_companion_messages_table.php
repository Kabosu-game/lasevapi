<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * COMPAGNON (remplace l'Assistant IA). Tous les textes sont écrits dans l'admin, aucun n'est généré.
 *  - kind = notification : rappels programmés sur le téléphone (« Tu n'as pas médité aujourd'hui »…).
 *    L'app récupère les règles et programme elle-même les notifications ; elle annule celle du jour
 *    dès que l'activité est faite.
 *  - kind = card : message affiché en haut de « Mon compagnon » à l'ouverture.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companion_messages', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20)->default('notification')->index();
            $table->string('trigger', 40)->index();
            $table->string('activity', 40)->nullable(); // activité suivie (meditation, gratitude, oracle…)
            $table->unsignedInteger('trigger_value')->nullable(); // nombre de jours selon le déclencheur
            $table->string('send_time', 5)->nullable(); // HH:MM (notifications)
            $table->string('weekdays', 7)->nullable(); // « 1234567 » (1 = lundi) ; vide = tous les jours
            $table->string('title', 120)->nullable();
            $table->text('body');
            $table->string('audio')->nullable(); // chemin sur le disque public (cartes)
            $table->string('action', 40)->nullable(); // écran à ouvrir dans l'app
            $table->string('action_label', 60)->nullable();
            $table->integer('priority')->default(0);
            $table->unsignedInteger('cooldown_hours')->default(24);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('companion_message_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('companion_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('shown_at');
            $table->index(['user_id', 'companion_message_id', 'shown_at'], 'companion_views_user_message_shown');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('companion_last_seen_at')->nullable();
        });

        $now = now();
        $row = fn (array $r) => array_merge([
            'kind' => 'notification', 'activity' => null, 'trigger_value' => null, 'send_time' => null,
            'weekdays' => null, 'title' => null, 'audio' => null, 'action' => null, 'action_label' => null,
            'priority' => 0, 'cooldown_hours' => 24, 'starts_at' => null, 'ends_at' => null,
            'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ], $r);

        DB::table('companion_messages')->insert(array_map($row, [
            // Les trois rendez-vous du jour (matin, après-midi, soir), seulement si rien n'a encore été fait.
            ['trigger' => 'nothing_done_today', 'send_time' => '08:30', 'title' => 'Bonjour {prenom} ☀️', 'body' => 'Ta journée commence : cinq minutes de méditation pour bien la démarrer ?', 'action' => 'meditation', 'priority' => 30],
            ['trigger' => 'nothing_done_today', 'send_time' => '14:30', 'title' => 'Une pause pour toi ?', 'body' => 'Tu n\'as encore rien fait pour toi aujourd\'hui, {prenom}. Deux minutes de respiration, maintenant ?', 'action' => 'breathing', 'priority' => 30],
            ['trigger' => 'nothing_done_today', 'send_time' => '20:30', 'title' => 'Il est encore temps 🌙', 'body' => 'La journée se termine, {prenom}. Une courte méditation avant de dormir ?', 'action' => 'meditation', 'priority' => 30],
            // Rappels par activité, seulement si l'activité n'est pas encore faite.
            ['trigger' => 'activity_not_done', 'activity' => 'meditation', 'send_time' => '12:30', 'title' => 'Ta pause méditation', 'body' => '{prenom}, tu n\'as pas encore médité aujourd\'hui. Dix minutes suffisent pour te recentrer.', 'action' => 'meditation', 'priority' => 20],
            ['trigger' => 'activity_not_done', 'activity' => 'meditation', 'send_time' => '19:30', 'title' => 'Toujours pas de méditation ?', 'body' => 'La journée file, {prenom} ! Offre-toi quelques minutes de calme avant ce soir.', 'action' => 'meditation', 'priority' => 30],
            ['trigger' => 'activity_not_done', 'activity' => 'breathing', 'send_time' => '10:00', 'title' => 'Respire un instant', 'body' => 'Inspire 4 secondes, expire 6 secondes. Deux minutes, et tu repars plus léger(ère).', 'action' => 'breathing', 'priority' => 10],
            ['trigger' => 'activity_not_done', 'activity' => 'mood', 'send_time' => '09:00', 'title' => 'Comment te sens-tu ce matin ?', 'body' => 'Note ton humeur du jour, {prenom}. Ça prend dix secondes.', 'action' => 'journal', 'priority' => 10],
            ['trigger' => 'activity_not_done', 'activity' => 'checkin', 'send_time' => '11:00', 'title' => 'Ton bilan du jour t\'attend', 'body' => 'Six questions rapides pour recevoir des propositions adaptées à ton état du jour.', 'action' => 'checkin', 'priority' => 15],
            ['trigger' => 'activity_not_done', 'activity' => 'gratitude', 'send_time' => '21:00', 'title' => 'Trois mercis avant de dormir', 'body' => 'Qu\'est-ce qui t\'a fait du bien aujourd\'hui, {prenom} ? Note-le dans ton journal.', 'action' => 'journal', 'priority' => 20],
            ['trigger' => 'activity_not_done', 'activity' => 'introspection', 'send_time' => '18:00', 'weekdays' => '36', 'title' => 'Face à toi', 'body' => 'Ta prochaine porte intérieure t\'attend. Prends un moment rien que pour toi.', 'action' => 'inner_gates', 'priority' => 5],
            // Série, absence, Oracle, défis.
            ['trigger' => 'streak_at_risk', 'trigger_value' => 3, 'send_time' => '20:00', 'title' => 'Ta série est en danger !', 'body' => '{jours} jours d\'affilée, {prenom} ! Ne la casse pas ce soir : une seule activité suffit.', 'action' => 'activities', 'priority' => 50],
            ['trigger' => 'absent_days', 'trigger_value' => 1, 'send_time' => '18:30', 'title' => 'Tu nous as manqué hier', 'body' => 'Reprends en douceur, {prenom} : deux minutes de respiration et c\'est reparti.', 'action' => 'breathing', 'priority' => 40],
            ['trigger' => 'absent_days', 'trigger_value' => 3, 'send_time' => '18:30', 'title' => 'Ça fait trois jours…', 'body' => 'Ta routine bien-être t\'attend, {prenom}. Chaque jour est une nouvelle occasion de reprendre.', 'action' => 'meditation', 'priority' => 45],
            ['trigger' => 'absent_days', 'trigger_value' => 7, 'send_time' => '18:30', 'title' => 'On ne t\'oublie pas', 'body' => 'Une semaine sans toi, {prenom}. Reviens quand tu veux : ton espace est toujours là.', 'action' => 'activities', 'priority' => 45],
            ['trigger' => 'oracle_available', 'send_time' => '09:30', 'title' => 'Une carte t\'attend', 'body' => 'Ton tirage Oracle est disponible, {prenom}. Quelle carte va t\'appeler aujourd\'hui ?', 'action' => 'oracle', 'priority' => 25],
            ['trigger' => 'challenge_ending', 'trigger_value' => 3, 'send_time' => '17:00', 'title' => 'Ton défi se termine bientôt', 'body' => '« {defi} » se termine dans {jours} jour(s). Encore un effort, tu y es presque !', 'action' => 'challenges', 'priority' => 35],
            // Cartes affichées à l'ouverture de « Mon compagnon ».
            ['kind' => 'card', 'trigger' => 'welcome', 'cooldown_hours' => 0, 'body' => 'Heureuse de te retrouver, {prenom}. Ton évolution commence par les petites décisions que tu prends aujourd\'hui.'],
            ['kind' => 'card', 'trigger' => 'morning', 'cooldown_hours' => 20, 'priority' => 10, 'body' => 'Bonjour {prenom}. Commence ta journée par quelques respirations lentes : inspire 4 secondes, expire 6 secondes.', 'action' => 'breathing', 'action_label' => 'Respirer'],
            ['kind' => 'card', 'trigger' => 'evening', 'cooldown_hours' => 20, 'priority' => 10, 'body' => 'Bonsoir {prenom}. Avant de dormir, note trois choses pour lesquelles tu es reconnaissant(e) aujourd\'hui.', 'action' => 'journal', 'action_label' => 'Écrire'],
            ['kind' => 'card', 'trigger' => 'missed_yesterday', 'priority' => 30, 'body' => 'Hier a été plus calme, et c\'est normal. Aujourd\'hui est une nouvelle occasion de reprendre ta routine.'],
            ['kind' => 'card', 'trigger' => 'all_done_today', 'cooldown_hours' => 12, 'priority' => 40, 'body' => 'Tout est accompli pour aujourd\'hui, {prenom}. Bravo pour ta constance !'],
        ]));

        // La tuile d'accueil ne parle plus d'IA.
        if (Schema::hasTable('home_menu_items')) {
            DB::table('home_menu_items')->where('slug', 'assistant')->where('name', 'Assistant IA')
                ->update(['name' => 'Mon compagnon', 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('home_menu_items')) {
            DB::table('home_menu_items')->where('slug', 'assistant')->where('name', 'Mon compagnon')
                ->update(['name' => 'Assistant IA']);
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('companion_last_seen_at');
        });
        Schema::dropIfExists('companion_message_views');
        Schema::dropIfExists('companion_messages');
    }
};
