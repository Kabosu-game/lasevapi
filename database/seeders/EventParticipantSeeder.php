<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EventParticipant;
use App\Models\Event;

class EventParticipantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = Event::take(3)->get();
        
        if ($events->isEmpty()) {
            $this->command->warn('Aucun événement trouvé. Veuillez d\'abord exécuter EventSeeder.');
            return;
        }

        $participants = [
            ['first_name' => 'Jean', 'last_name' => 'Dupont'],
            ['first_name' => 'Marie', 'last_name' => 'Martin'],
            ['first_name' => 'Pierre', 'last_name' => 'Dubois'],
            ['first_name' => 'Sophie', 'last_name' => 'Bernard'],
            ['first_name' => 'Lucas', 'last_name' => 'Moreau'],
        ];

        $statuses = ['registered', 'cancelled', 'attended'];

        foreach ($events as $eventIndex => $event) {
            // Un seul participant par événement (contrainte unique sur event_id)
            $participant = $participants[$eventIndex % count($participants)];

            EventParticipant::firstOrCreate(
                ['event_id' => $event->id],
                [
                    'first_name' => $participant['first_name'],
                    'last_name' => $participant['last_name'],
                    'status' => $statuses[array_rand($statuses)],
                    'registered_at' => now()->subDays(rand(1, 10)),
                ]
            );
        }
    }
}

