<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Créer les catégories générales pour les blogs, méditations, événements
     */
    public function run(): void
    {
        $categories = [
            // Catégories pour les blogs
            ['name' => 'Bien-être', 'type' => 'blog'],
            ['name' => 'Méditation', 'type' => 'blog'],
            ['name' => 'Développement personnel', 'type' => 'blog'],
            ['name' => 'Santé', 'type' => 'blog'],
            ['name' => 'Spiritualité', 'type' => 'blog'],
            
            // Catégories pour les méditations
            ['name' => 'Méditation guidée', 'type' => 'meditation'],
            ['name' => 'Méditation de pleine conscience', 'type' => 'meditation'],
            ['name' => 'Méditation pour le sommeil', 'type' => 'meditation'],
            ['name' => 'Méditation matinale', 'type' => 'meditation'],
            ['name' => 'Méditation anti-stress', 'type' => 'meditation'],
            
            // Catégories pour les événements
            ['name' => 'Retraite spirituelle', 'type' => 'event'],
            ['name' => 'Atelier de méditation', 'type' => 'event'],
            ['name' => 'Session de groupe', 'type' => 'event'],
            ['name' => 'Formation', 'type' => 'event'],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['name' => $category['name'], 'type' => $category['type']],
                [
                    'name' => $category['name'],
                    'type' => $category['type'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}

