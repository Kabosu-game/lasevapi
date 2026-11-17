<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FITNESS CENTER : programmes (yoga par niveau, entraînements ; 7, 21 ou 30 jours),
 * leçons vidéo par jour, et séances réalisées par les utilisateurs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_programs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 16)->default('yoga'); // yoga, training
            $table->string('level', 16)->default('debutant'); // debutant, intermediaire, avance
            $table->unsignedSmallInteger('duration_days')->default(7); // 7, 21, 30
            $table->string('image')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('fitness_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fitness_program_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('day_number')->default(1);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('video_path')->nullable();
            $table->string('video_url')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['fitness_program_id', 'day_number']);
        });

        Schema::create('fitness_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fitness_lesson_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->index(['user_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_sessions');
        Schema::dropIfExists('fitness_lessons');
        Schema::dropIfExists('fitness_programs');
    }
};
