<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use Carbon\Carbon;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = [
            [
                'title' => 'Retraite de méditation en pleine nature',
                'description' => 'Une retraite de 3 jours pour se reconnecter avec soi-même et la nature.',
                'event_date' => Carbon::now()->addMonths(2)->setTime(9, 0),
                'location' => 'Centre de méditation de la Forêt, Sénégal',
                'price' => 150000.00,
                'current_participants' => 5,
                'status' => 'upcoming',
            ],
            [
                'title' => 'Atelier de développement personnel',
                'description' => 'Un atelier d\'une journée pour développer votre confiance en vous et vos compétences.',
                'event_date' => Carbon::now()->addWeeks(3)->setTime(10, 0),
                'location' => 'Espace Bien-être, Dakar',
                'price' => 25000.00,
                'current_participants' => 12,
                'status' => 'upcoming',
            ],
            [
                'title' => 'Session de méditation de groupe',
                'description' => 'Rejoignez-nous pour une session de méditation collective et bienveillante.',
                'event_date' => Carbon::now()->addWeeks(1)->setTime(18, 0),
                'location' => 'Espace Zen, Dakar',
                'price' => 5000.00,
                'current_participants' => 8,
                'status' => 'upcoming',
            ],
            [
                'title' => 'Formation à la pleine conscience',
                'description' => 'Une formation complète de 2 jours sur les techniques de pleine conscience.',
                'event_date' => Carbon::now()->addMonths(1)->setTime(9, 0),
                'location' => 'Centre de Formation, Dakar',
                'price' => 75000.00,
                'current_participants' => 15,
                'status' => 'upcoming',
            ],
            [
                'title' => 'Cérémonie de gratitude',
                'description' => 'Une cérémonie spéciale pour cultiver la gratitude et partager des moments de joie.',
                'event_date' => Carbon::now()->addDays(10)->setTime(17, 0),
                'location' => 'Jardin de la Paix, Dakar',
                'price' => 0.00,
                'current_participants' => 20,
                'status' => 'upcoming',
            ],
        ];

        foreach ($events as $eventData) {
            Event::updateOrCreate(
                [
                    'title' => $eventData['title'],
                    'event_date' => $eventData['event_date'],
                ],
                $eventData
            );
        }
    }
}

