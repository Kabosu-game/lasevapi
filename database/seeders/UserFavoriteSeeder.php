<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserFavorite;

class UserFavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Note: La table user_favorites semble incomplète dans les migrations.
     * Ce seeder est créé mais peut nécessiter des ajustements selon la structure finale de la table.
     */
    public function run(): void
    {
        // La table user_favorites n'a que id et timestamps dans la migration actuelle
        // Ce seeder est prêt mais peut nécessiter des ajustements selon la structure finale
        
        $this->command->info('UserFavoriteSeeder: La table user_favorites semble incomplète. Vérifiez la migration.');
        
        // Si la table est mise à jour avec user_id et favoritable_type/id,
        // décommentez le code ci-dessous après avoir mis à jour la migration
        
        /*
        $users = User::where('role', 'user')->take(3)->get();
        
        if ($users->isEmpty()) {
            $this->command->warn('Aucun utilisateur trouvé. Veuillez d\'abord exécuter UserSeeder.');
            return;
        }

        foreach ($users as $user) {
            // Ajouter des méditations favorites
            $meditations = Meditation::take(2)->get();
            foreach ($meditations as $meditation) {
                UserFavorite::updateOrCreate([
                    'user_id' => $user->id,
                    'favoritable_type' => Meditation::class,
                    'favoritable_id' => $meditation->id,
                ]);
            }

            // Ajouter des blogs favorites
            $blogs = Blog::take(2)->get();
            foreach ($blogs as $blog) {
                UserFavorite::updateOrCreate([
                    'user_id' => $user->id,
                    'favoritable_type' => Blog::class,
                    'favoritable_id' => $blog->id,
                ]);
            }

            // Ajouter des affirmations favorites
            $affirmations = Affirmation::take(3)->get();
            foreach ($affirmations as $affirmation) {
                UserFavorite::updateOrCreate([
                    'user_id' => $user->id,
                    'favoritable_type' => Affirmation::class,
                    'favoritable_id' => $affirmation->id,
                ]);
            }
        }
        */
    }
}

