<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * Les seeders sont exécutés dans un ordre spécifique pour respecter les dépendances.
     */
    public function run(): void
    {
        $this->call([
            // 1. Utilisateurs (doit être en premier car d'autres tables en dépendent)
            UserSeeder::class,
            
            // 2. Catégories et objectifs (tables indépendantes)
            CategorySeeder::class,
            ObjectiveSeeder::class,
            AffirmationCategorySeeder::class, // Crée aussi les affirmations
            
            // 3. Plans de retraite (indépendant)
            RetreatPlanSeeder::class,
            
            // 3b. Cuisine : plats et chefs
            DishSeeder::class,
            ChefSeeder::class,
            
            // 4. Contenu (méditations, blogs) - dépendent des utilisateurs pour les auteurs
            MeditationSeeder::class,
            BlogSeeder::class,
            
            // 5. Événements
            EventSeeder::class,
            
            // 6. Citations quotidiennes
            DailyQuoteSeeder::class,
            
            // 6b. Menus page d'accueil (Affirmation, Meditation, etc. + images)
            HomeMenuItemSeeder::class,
            
            // 7. Données utilisateur (dépendent des utilisateurs et autres tables)
            SubscriptionSeeder::class,
            GratitudeJournalSeeder::class,
            FoodComfortFormSeeder::class,
            UserObjectiveSeeder::class,
            UserFavoriteSeeder::class,
            
            // 8. Données liées aux événements et retraites
            EventParticipantSeeder::class,
            PaymentSeeder::class,
        ]);

        // Note: AppSettings est déjà créé dans la migration
        // Si vous voulez réinitialiser ou modifier les paramètres, créez un AppSettingSeeder
    }
}
