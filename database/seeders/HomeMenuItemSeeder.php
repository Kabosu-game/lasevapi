<?php

namespace Database\Seeders;

use App\Models\HomeMenuItem;
use Illuminate\Database\Seeder;

class HomeMenuItemSeeder extends Seeder
{
    public function run(): void
    {
        // Ordre de la page d'accueil de l'app (modifiable ensuite dans l'admin).
        $items = [
            ['slug' => 'assistant', 'name' => 'Assistant IA'],
            ['slug' => 'oracles', 'name' => 'Oracles'],
            ['slug' => 'activities', 'name' => 'Activités'],
            ['slug' => 'fitness', 'name' => 'Fitness Center'],
            ['slug' => 'journal', 'name' => 'My Journal'],
            ['slug' => 'challenges', 'name' => 'Défis du mois'],
            ['slug' => 'inner-gates', 'name' => 'Face à toi'],
            ['slug' => 'sacred-space', 'name' => 'Espace Sacré des Lois'],
            ['slug' => 'articles', 'name' => 'Le pouvoir secret'],
            ['slug' => 'events', 'name' => 'Événements'],
            ['slug' => 'retreats', 'name' => 'Plan de retraite'],
            ['slug' => 'cuisine', 'name' => 'Cuisine'],
        ];

        foreach ($items as $i => $data) {
            HomeMenuItem::firstOrCreate(
                ['slug' => $data['slug']],
                ['name' => $data['name'], 'sort_order' => $i + 1, 'is_visible' => true]
            );
        }
    }
}
