<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Meditation;
use Illuminate\Support\Str;

class MeditationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $meditations = [
            [
                'title' => 'Méditation de pleine conscience - 10 minutes',
                'description' => 'Une méditation guidée de 10 minutes pour vous aider à vous recentrer et à trouver la paix intérieure.',
                'duration' => 10,
            ],
            [
                'title' => 'Méditation du matin - Énergie positive',
                'description' => 'Commencez votre journée avec une énergie positive grâce à cette méditation matinale.',
                'duration' => 15,
            ],
            [
                'title' => 'Méditation pour le sommeil profond',
                'description' => 'Détendez-vous complètement et préparez-vous à un sommeil réparateur avec cette méditation guidée.',
                'duration' => 20,
            ],
            [
                'title' => 'Méditation anti-stress - Libération des tensions',
                'description' => 'Libérez toutes les tensions accumulées et retrouvez votre calme intérieur.',
                'duration' => 12,
            ],
            [
                'title' => 'Méditation de gratitude',
                'description' => 'Cultivez la gratitude et appréciez les bénédictions de votre vie.',
                'duration' => 8,
            ],
            [
                'title' => 'Méditation pour la confiance en soi',
                'description' => 'Renforcez votre confiance en vous et développez une image positive de vous-même.',
                'duration' => 15,
            ],
            [
                'title' => 'Méditation en nature - Forêt virtuelle',
                'description' => 'Immergez-vous dans les sons de la nature pour une relaxation profonde.',
                'duration' => 25,
            ],
            [
                'title' => 'Méditation de respiration - 5 minutes express',
                'description' => 'Une séance rapide de 5 minutes pour vous recentrer rapidement.',
                'duration' => 5,
            ],
        ];

        foreach ($meditations as $meditationData) {
            $slug = Str::slug($meditationData['title']);
            
            Meditation::updateOrCreate(
                ['slug' => $slug],
                array_merge($meditationData, ['slug' => $slug])
            );
        }
    }
}

