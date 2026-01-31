<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GratitudeJournal;
use App\Models\User;
use Carbon\Carbon;

class GratitudeJournalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', 'user')->take(3)->get();
        
        if ($users->isEmpty()) {
            $this->command->warn('Aucun utilisateur trouvé pour créer les journaux de gratitude. Veuillez d\'abord exécuter UserSeeder.');
            return;
        }

        $gratitudeEntries = [
            [
                'title' => 'Journée ensoleillée',
                'positive_thing_1' => 'J\'ai passé un excellent moment en famille aujourd\'hui.',
                'positive_thing_2' => 'J\'ai terminé mon projet important avec succès.',
                'positive_thing_3' => 'Le temps était magnifique et j\'ai pu faire une promenade.',
            ],
            [
                'title' => 'Accomplissements',
                'positive_thing_1' => 'J\'ai réussi à méditer 20 minutes ce matin.',
                'positive_thing_2' => 'Un ami m\'a appelé pour prendre de mes nouvelles.',
                'positive_thing_3' => 'J\'ai appris quelque chose de nouveau aujourd\'hui.',
            ],
            [
                'title' => 'Moments de paix',
                'positive_thing_1' => 'J\'ai eu un sommeil réparateur cette nuit.',
                'positive_thing_2' => 'J\'ai pu aider quelqu\'un dans le besoin.',
                'positive_thing_3' => 'La méditation m\'a apporté beaucoup de sérénité.',
            ],
        ];

        foreach ($users as $userIndex => $user) {
            // Créer des entrées pour les 7 derniers jours pour chaque utilisateur
            for ($day = 0; $day < 7; $day++) {
                $journalDate = Carbon::now()->subDays($day);
                $entryIndex = ($userIndex + $day) % count($gratitudeEntries);
                $entry = $gratitudeEntries[$entryIndex];

                GratitudeJournal::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'journal_date' => $journalDate->format('Y-m-d'),
                    ],
                    array_merge($entry, [
                        'user_id' => $user->id,
                        'journal_date' => $journalDate->format('Y-m-d'),
                    ])
                );
            }
        }
    }
}

