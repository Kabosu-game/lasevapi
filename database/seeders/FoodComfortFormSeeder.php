<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FoodComfortForm;
use App\Models\User;

class FoodComfortFormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', 'user')->take(3)->get();
        
        if ($users->isEmpty()) {
            $this->command->warn('Aucun utilisateur trouvé pour créer les formulaires alimentaires. Veuillez d\'abord exécuter UserSeeder.');
            return;
        }

        $forms = [
            [
                'first_name' => 'Jean',
                'last_name' => 'Dupont',
                'has_allergies' => true,
                'allergy_food' => 'Arachides',
                'allergy_reaction' => 'Réaction cutanée et difficultés respiratoires',
                'allergy_type' => json_encode(['ingestion', 'contact']),
                'intolerances' => 'Lactose',
                'specific_diets' => json_encode(['sans_gluten']),
                'happiness_ingredients' => json_encode(['Chocolat', 'Fruits frais', 'Fromage']),
                'disliked_foods' => 'Chou-fleur, brocoli',
                'spice_level' => 'medium',
                'culinary_inspirations' => json_encode(['asiatique', 'mediterraneen']),
                'comfort_dish' => 'Poulet yassa avec riz',
                'breakfast_preference' => 'sucre',
                'hot_drinks' => json_encode(['cafe', 'the_noir']),
                'plant_drinks' => json_encode(['lait_soja']),
                'needs_snacks' => true,
                'free_comments' => 'J\'apprécie les repas équilibrés et colorés.',
            ],
            [
                'first_name' => 'Marie',
                'last_name' => 'Martin',
                'has_allergies' => false,
                'intolerances' => null,
                'specific_diets' => json_encode(['vegetarien']),
                'happiness_ingredients' => json_encode(['Avocat', 'Tomates', 'Basilic']),
                'disliked_foods' => 'Poisson',
                'spice_level' => 'tres_doux',
                'culinary_inspirations' => json_encode(['mediterraneen', 'terroir']),
                'comfort_dish' => 'Risotto aux champignons',
                'breakfast_preference' => 'sucre',
                'hot_drinks' => json_encode(['infusion']),
                'plant_drinks' => json_encode(['lait_avoine']),
                'needs_snacks' => false,
                'free_comments' => 'Je préfère les plats végétariens simples et savoureux.',
            ],
            [
                'first_name' => 'Pierre',
                'last_name' => 'Dubois',
                'has_allergies' => false,
                'intolerances' => null,
                'specific_diets' => json_encode(['halal']),
                'happiness_ingredients' => json_encode(['Viande grillée', 'Épices', 'Légumes frais']),
                'disliked_foods' => 'Fruits de mer',
                'spice_level' => 'releve',
                'culinary_inspirations' => json_encode(['orientale', 'asiatique']),
                'comfort_dish' => 'Couscous royal',
                'breakfast_preference' => 'sale',
                'hot_drinks' => json_encode(['cafe', 'the_noir']),
                'plant_drinks' => json_encode(['lait_animal']),
                'needs_snacks' => true,
                'free_comments' => 'J\'aime les plats épicés et généreux en portions.',
            ],
        ];

        foreach ($users as $index => $user) {
            if (isset($forms[$index])) {
                FoodComfortForm::updateOrCreate(
                    ['user_id' => $user->id],
                    array_merge($forms[$index], ['user_id' => $user->id])
                );
            }
        }
    }
}

