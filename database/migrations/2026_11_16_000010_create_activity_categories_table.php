<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Espace ACTIVITÉS : les catégories (nom, texte, icône, couleur, ordre) sont gérées
 * dans l'admin au lieu d'être écrites dans l'app. La clé correspond au champ
 * « category » des méditations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_categories', function (Blueprint $table) {
            $table->id();
            $table->string('key', 32)->unique();
            $table->string('name');
            $table->string('description', 500)->nullable();
            $table->string('icon', 40)->default('headphones');
            $table->string('color', 7)->default('#2E7D6B');
            // Affiche l'accès aux exercices de respiration guidés dans la catégorie.
            $table->boolean('with_breathing_exercises')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        $categories = [
            ['meditation', 'Méditation', 'Musiques et sons pour méditer.', 'self_improvement', '#2E7D6B', false],
            ['jogging', 'Jogging', 'Musiques pour courir.', 'directions_run', '#D9822B', false],
            ['relaxation', 'Relaxation', 'Sons relaxants et détente.', 'spa', '#B07A4F', false],
            ['sommeil', 'Sommeil', 'Musiques pour dormir.', 'bedtime', '#3F51B5', false],
            ['concentration', 'Concentration', 'Musique pour travailler.', 'headphones', '#7C8B3A', false],
            ['respiration', 'Respiration', 'Exercices respiratoires guidés.', 'air', '#4A90B8', true],
        ];
        foreach ($categories as $i => [$key, $name, $description, $icon, $color, $breathing]) {
            DB::table('activity_categories')->insert([
                'key' => $key,
                'name' => $name,
                'description' => $description,
                'icon' => $icon,
                'color' => $color,
                'with_breathing_exercises' => $breathing,
                'sort_order' => $i + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_categories');
    }
};
