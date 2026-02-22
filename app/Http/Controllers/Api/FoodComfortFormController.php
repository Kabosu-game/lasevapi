<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoodComfortForm;
use App\Models\User;
use App\Models\RetreatPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FoodComfortFormController extends Controller
{
    // Soumettre le formulaire food comfort et créer un compte utilisateur
    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'retreatName' => 'required|string|max:255',
            'retreatDates' => 'nullable|string',
            'retreat_plan_id' => 'nullable|integer|exists:retreat_plans,id',
            'hasAllergy' => 'nullable|boolean',
            'allergyDetails' => 'nullable|string',
            'isContactAllergy' => 'nullable|boolean',
            'isIngestionAllergy' => 'nullable|boolean',
            'intolerances' => 'nullable|string',
            'dietPreference' => 'nullable|string',
            'otherDiet' => 'nullable|string',
            'ingredients' => 'nullable|array',
            'dislikedFood' => 'nullable|string',
            'spiceLevel' => 'nullable|string',
            'cuisinePreferences' => 'nullable|array',
            'comfortDish' => 'nullable|string',
            'breakfastPreference' => 'nullable|string',
            'hotDrinks' => 'nullable|array',
            'otherHotDrink' => 'nullable|string',
            'vegetableMilk' => 'nullable|string',
            'needsSnacks' => 'nullable|boolean',
            'comments' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Trouver le plan de retraite par nom si retreat_plan_id n'est pas fourni
        $retreatPlanId = $request->retreat_plan_id;
        if (!$retreatPlanId && $request->retreatName) {
            $retreatPlan = RetreatPlan::where('title', $request->retreatName)->first();
            if ($retreatPlan) {
                $retreatPlanId = $retreatPlan->id;
            }
        }

        // Générer un email unique et un mot de passe
        $nameParts = explode(' ', $request->name, 2);
        $firstName = $nameParts[0] ?? 'User';
        $lastName = $nameParts[1] ?? '';
        
        // Générer un identifiant unique basé sur le timestamp
        $userId = 'USR' . strtoupper(Str::random(8));
        // Générer un email unique avec timestamp et nombre aléatoire
        $baseEmail = strtolower(preg_replace('/[^a-z0-9]/', '.', $request->name));
        $email = $baseEmail . '.' . time() . '.' . rand(1000, 9999) . '@retraite.lasev';
        $password = 'RET' . date('d') . date('m') . date('y') . '!';
        
        // Créer un nouvel utilisateur (toujours créer un nouveau compte)
        $user = User::create([
            'name' => $request->name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'user',
            'device_id' => $userId,
            'date_of_birth' => now()->subYears(18)->toDateString(), // Default to 18 years ago
        ]);

        // Préparer les données du formulaire
        $formData = [
            'user_id' => $user->id,
            'retreat_plan_id' => $retreatPlanId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'has_allergies' => $request->hasAllergy ?? false,
            'allergy_food' => $request->allergyDetails,
            'allergy_reaction' => $request->allergyDetails,
            'allergy_type' => [],
            'intolerances' => $request->intolerances,
            'specific_diets' => $this->normalizeDietPreference($request->dietPreference, $request->otherDiet),
            'happiness_ingredients' => $request->ingredients ?? [],
            'disliked_foods' => $request->dislikedFood,
            'spice_level' => $this->normalizeSpiceLevel($request->spiceLevel),
            'culinary_inspirations' => $request->cuisinePreferences ?? [],
            'comfort_dish' => $request->comfortDish,
            'breakfast_preference' => $this->normalizeBreakfastPreference($request->breakfastPreference),
            'hot_drinks' => $this->normalizeHotDrinks($request->hotDrinks, $request->otherHotDrink),
            'plant_drinks' => $this->normalizePlantDrinks($request->vegetableMilk),
            'needs_snacks' => $request->needsSnacks ?? false,
            'free_comments' => $request->comments,
        ];

        // Gérer le type d'allergie
        if ($request->isContactAllergy) {
            $formData['allergy_type'][] = 'contact';
        }
        if ($request->isIngestionAllergy) {
            $formData['allergy_type'][] = 'ingestion';
        }

        // Encoder les champs JSON
        foreach ([
            'allergy_type', 'specific_diets', 'happiness_ingredients', 
            'culinary_inspirations', 'hot_drinks', 'plant_drinks'
        ] as $jsonField) {
            if (isset($formData[$jsonField])) {
                $formData[$jsonField] = json_encode($formData[$jsonField]);
            }
        }

        // Créer ou mettre à jour le formulaire
        $form = FoodComfortForm::updateOrCreate(
            ['user_id' => $user->id],
            $formData
        );

        return response()->json([
            'success' => true,
            'data' => [
                'form' => $form,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $email,
                    'username' => $userId,
                    'password' => $password,
                ],
                'retreat_plan_id' => $retreatPlanId,
            ],
        ], 201);
    }

    private function normalizeDietPreference($dietPreference, $otherDiet)
    {
        if (!$dietPreference) return [];
        
        $diets = [];
        if (str_contains(strtolower($dietPreference), 'végétarien')) {
            $diets[] = 'vegetarien';
        }
        if (str_contains(strtolower($dietPreference), 'vegan')) {
            $diets[] = 'vegan';
        }
        if (str_contains(strtolower($dietPreference), 'sans gluten')) {
            $diets[] = 'sans_gluten';
        }
        if (str_contains(strtolower($dietPreference), 'sans porc')) {
            $diets[] = 'sans_porc';
        }
        if ($dietPreference === 'Autre' && $otherDiet) {
            $diets[] = $otherDiet;
        }
        
        return $diets;
    }

    private function normalizeSpiceLevel($spiceLevel)
    {
        if (!$spiceLevel) return null;
        
        $map = [
            'Très doux' => 'tres_doux',
            'Médium' => 'medium',
            'Relevé' => 'releve',
        ];
        
        return $map[$spiceLevel] ?? 'medium';
    }

    private function normalizeBreakfastPreference($preference)
    {
        if (!$preference) return null;
        
        $map = [
            'Sucré' => 'sucre',
            'Salé' => 'sale',
            'Pas de petit-déjeuner' => 'pas_de_petit_dej',
        ];
        
        return $map[$preference] ?? null;
    }

    private function normalizeHotDrinks($hotDrinks, $otherHotDrink)
    {
        $drinks = [];
        if (is_array($hotDrinks)) {
            foreach ($hotDrinks as $drink) {
                $drink = strtolower($drink);
                if (str_contains($drink, 'café')) {
                    $drinks[] = 'cafe';
                } elseif (str_contains($drink, 'thé noir')) {
                    $drinks[] = 'the_noir';
                } elseif (str_contains($drink, 'infusion')) {
                    $drinks[] = 'infusion';
                } elseif (str_contains($drink, 'autre') && $otherHotDrink) {
                    $drinks[] = $otherHotDrink;
                }
            }
        }
        return $drinks;
    }

    private function normalizePlantDrinks($vegetableMilk)
    {
        if (!$vegetableMilk) return [];
        
        $drinks = [];
        $vegetableMilk = strtolower($vegetableMilk);
        if (str_contains($vegetableMilk, 'avoine')) {
            $drinks[] = 'avoine';
        }
        if (str_contains($vegetableMilk, 'amande')) {
            $drinks[] = 'amande';
        }
        if (str_contains($vegetableMilk, 'soja')) {
            $drinks[] = 'soja';
        }
        if (str_contains($vegetableMilk, 'lait animal')) {
            $drinks[] = 'lait_animal';
        }
        
        return $drinks;
    }
}
