<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Objective;

class UserObjectiveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', 'user')->get();
        $objectives = Objective::all();
        
        if ($users->isEmpty() || $objectives->isEmpty()) {
            $this->command->warn('Aucun utilisateur ou objectif trouvé. Veuillez d\'abord exécuter UserSeeder et ObjectiveSeeder.');
            return;
        }

        foreach ($users as $user) {
            // Chaque utilisateur a 2-4 objectifs aléatoires
            $selectedObjectives = $objectives->random(min(4, $objectives->count()));
            
            foreach ($selectedObjectives as $objective) {
                DB::table('user_objectives')->updateOrInsert(
                    [
                        'user_id' => $user->id,
                        'objective_id' => $objective->id,
                    ],
                    [
                        'user_id' => $user->id,
                        'objective_id' => $objective->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}

