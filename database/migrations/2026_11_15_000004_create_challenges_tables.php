<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DÉFIS DU MOIS : défis, participants (points, jours accomplis) et validations quotidiennes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('objective');
            $table->string('type', 32)->default('other'); // meditation, gratitude, yoga, hydration, discipline, spiritual, other
            $table->unsignedSmallInteger('target_days');
            $table->unsignedSmallInteger('points_per_day')->default(10);
            $table->unsignedSmallInteger('completion_bonus')->default(50);
            $table->string('badge_name')->nullable();
            $table->text('reward')->nullable();
            $table->string('image')->nullable();
            $table->date('starts_at');
            $table->date('ends_at');
            $table->boolean('is_published')->default(true);
            $table->boolean('recurring_monthly')->default(false);
            $table->foreignId('parent_challenge_id')->nullable()->constrained('challenges')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['is_published', 'starts_at', 'ends_at']);
        });

        Schema::create('challenge_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('days_completed')->default(0);
            $table->unsignedInteger('points')->default(0);
            $table->date('last_checkin_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('final_rank')->nullable();
            $table->boolean('reward_granted')->default(false);
            $table->timestamps();

            $table->unique(['challenge_id', 'user_id']);
            $table->index(['challenge_id', 'points']);
        });

        Schema::create('challenge_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_participant_id')->constrained()->cascadeOnDelete();
            $table->date('checkin_date');
            $table->timestamps();

            $table->unique(['challenge_participant_id', 'checkin_date']);
        });

        $this->seedExamples();
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_checkins');
        Schema::dropIfExists('challenge_participants');
        Schema::dropIfExists('challenges');
    }

    /** Défis d'exemple du cahier des charges, pour le mois en cours, renouvelés automatiquement. */
    private function seedExamples(): void
    {
        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();
        $days = now()->daysInMonth;
        $examples = [
            ['21 jours de méditation', 'Méditer quotidiennement', 'meditation', 21, 'Esprit serein'],
            ['30 jours de gratitude', 'Écrire chaque jour', 'gratitude', min(30, $days), 'Cœur reconnaissant'],
            ['Défi yoga', 'Réaliser les séances', 'yoga', 15, 'Corps en harmonie'],
            ['Défi hydratation', 'Suivre son hydratation', 'hydration', 21, 'Source de vie'],
            ['Défi discipline', 'Respecter sa routine', 'discipline', 21, 'Volonté d\'acier'],
            ['Défi spirituel', 'Réaliser un parcours d\'introspection', 'spiritual', 14, 'Âme éveillée'],
        ];
        $now = now();
        DB::table('challenges')->insert(array_map(fn ($e) => [
            'title' => $e[0],
            'objective' => $e[1],
            'description' => $e[1] . ' pendant ' . $e[3] . ' jours ce mois-ci. Valide chaque jour ta participation pour gagner des points et grimper au classement.',
            'type' => $e[2],
            'target_days' => $e[3],
            'points_per_day' => 10,
            'completion_bonus' => 50,
            'badge_name' => $e[4],
            'starts_at' => $start,
            'ends_at' => $end,
            'is_published' => true,
            'recurring_monthly' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $examples));
    }
};
