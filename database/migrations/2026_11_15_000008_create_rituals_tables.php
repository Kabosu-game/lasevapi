<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ESPACE SACRÉ DES LOIS — bibliothèque de rituels.
 * Chaque tutoriel présente ses objectifs, le matériel, les étapes, les précautions
 * et les traditions auxquelles il se rattache. Le contenu est saisi dans l'admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ritual_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon', 40)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('rituals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ritual_category_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->json('objectives')->nullable();
            $table->json('materials')->nullable();
            $table->json('steps')->nullable(); // [{title, text}]
            $table->json('precautions')->nullable();
            $table->text('traditions')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('image')->nullable();
            $table->string('video_url', 500)->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['ritual_category_id', 'is_published']);
        });

        $now = now();
        $categories = [
            ['tutoriels', 'Tutoriels de rituels', 'Apprendre pas à pas les rituels et leur déroulement.', 'menu_book'],
            ['espaces-sacres', 'Préparation des espaces sacrés', 'Préparer, purifier et entretenir un lieu dédié à la pratique.', 'home'],
            ['plantes', 'Plantes et leurs usages traditionnels', 'Connaître les plantes et la place qu\'elles occupent dans les traditions.', 'eco'],
            ['purification', 'Rituels de purification', 'Se libérer de ce qui pèse et repartir sur une base apaisée.', 'water_drop'],
            ['reconnexion', 'Reconnexion spirituelle', 'Retrouver le lien avec soi, les ancêtres et le sacré.', 'self_improvement'],
            ['prieres', 'Prières et méditations', 'Paroles, chants et temps de recueillement.', 'spa'],
            ['traditions', 'Enseignements sur les traditions des Lois', 'Comprendre l\'histoire, les symboles et le sens des traditions.', 'auto_stories'],
        ];
        DB::table('ritual_categories')->insert(array_map(fn ($c, $i) => [
            'slug' => $c[0],
            'name' => $c[1],
            'description' => $c[2],
            'icon' => $c[3],
            'sort_order' => $i + 1,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $categories, array_keys($categories)));
    }

    public function down(): void
    {
        Schema::dropIfExists('rituals');
        Schema::dropIfExists('ritual_categories');
    }
};
