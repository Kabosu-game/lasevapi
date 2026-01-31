<?php

namespace Database\Seeders;

use App\Models\HomeMenuItem;
use Illuminate\Database\Seeder;

class HomeMenuItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['slug' => 'affirmation', 'name' => 'Affirmation', 'sort_order' => 1],
            ['slug' => 'meditation', 'name' => 'Meditation', 'sort_order' => 2],
            ['slug' => 'articles', 'name' => 'Le pouvoir secret', 'sort_order' => 3],
            ['slug' => 'gratitude', 'name' => 'Gratitude', 'sort_order' => 4],
            ['slug' => 'events', 'name' => 'Evenement', 'sort_order' => 5],
            ['slug' => 'retreats', 'name' => 'Plan de retraite', 'sort_order' => 6],
            ['slug' => 'cuisine', 'name' => 'Cuisine', 'sort_order' => 7],
        ];

        foreach ($items as $data) {
            HomeMenuItem::firstOrCreate(
                ['slug' => $data['slug']],
                ['name' => $data['name'], 'sort_order' => $data['sort_order']]
            );
        }
    }
}
