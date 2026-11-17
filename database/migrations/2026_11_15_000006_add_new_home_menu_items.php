<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nouvelle architecture de l'accueil : Oracles remplace Affirmation, Activités remplace
 * Meditation, My Journal reprend Gratitude ; ajout de Fitness Center, Défis du mois et Assistant IA.
 * Les anciennes lignes sont conservées (non affichées par l'app) pour ne pas perdre leurs images.
 */
return new class extends Migration
{
    private array $items = [
        ['slug' => 'assistant', 'name' => 'Assistant IA', 'sort_order' => 1],
        ['slug' => 'oracles', 'name' => 'Oracles', 'sort_order' => 2],
        ['slug' => 'activities', 'name' => 'Activités', 'sort_order' => 3],
        ['slug' => 'fitness', 'name' => 'Fitness Center', 'sort_order' => 4],
        ['slug' => 'journal', 'name' => 'My Journal', 'sort_order' => 5],
        ['slug' => 'challenges', 'name' => 'Défis du mois', 'sort_order' => 6],
    ];

    public function up(): void
    {
        $now = now();
        foreach ($this->items as $item) {
            if (!DB::table('home_menu_items')->where('slug', $item['slug'])->exists()) {
                DB::table('home_menu_items')->insert($item + ['created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        DB::table('home_menu_items')->whereIn('slug', array_column($this->items, 'slug'))->delete();
    }
};
