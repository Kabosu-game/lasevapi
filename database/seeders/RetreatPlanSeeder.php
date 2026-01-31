<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RetreatPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Récupère les 3 plans de retraite exacts depuis la page Cuisine de l'app Flutter
     */
    public function run(): void
    {
        // Supprimer les anciens plans pour éviter les doublons
        DB::table('retreat_plans')->truncate();

        // Les 3 plans de retraite avec les prix configurés
        $retreatPlans = [
            [
                'title' => 'Plan Essentiel',
                'description' => 'Ce plan offre aux participants un accès aux ressources essentielles pour débuter leur transformation personnelle',
                'duration_days' => 7,
                'cover_image' => null,
                'features' => json_encode([
                    'Accès aux méditations guidées',
                    'Journal de gratitude numérique',
                    'Affirmations quotidiennes',
                    'Support par email',
                ]),
                'tags' => json_encode([
                    'Essentiel',
                    'Débutant',
                    'Support email',
                    'Ressources de base',
                ]),
                'services' => json_encode([
                    'Méditations guidées',
                    'Journal de gratitude',
                    'Affirmations quotidiennes',
                    'Support par email',
                ]),
                'status' => 'available',
                'price' => 2000,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'title' => 'Plan Standard',
                'description' => 'Une immersion complète de 14 jours avec accompagnement et ressources étendues pour une transformation progressive',
                'duration_days' => 14,
                'cover_image' => null,
                'features' => json_encode([
                    'Tous les services du Plan Essentiel',
                    'Espace ZEN en pleine nature',
                    'Confort matériel optimal',
                    'Libération émotionnelle',
                    'Support par téléphone',
                    'Imersion progressive de 14 jours',
                ]),
                'tags' => json_encode([
                    'Standard',
                    'Intermédiaire',
                    'Support téléphone',
                    'Immersion complète',
                ]),
                'services' => json_encode([
                    'Méditations guidées',
                    'Journal de gratitude',
                    'Affirmations quotidiennes',
                    'Espace ZEN',
                    'Confort matériel',
                    'Support par téléphone',
                    'Immersion progressive',
                ]),
                'status' => 'available',
                'price' => 3000,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'title' => 'Plan Premium',
                'description' => 'Un programme structuré autour de 3 piliers avec accompagnement personnalisé et chef de cuisine privé pour une transformation complète',
                'duration_days' => 14,
                'cover_image' => null,
                'features' => json_encode([
                    'Tous les services du Plan Standard',
                    'Pilier I: Libération du Passé (Jour 1-5)',
                    'Pilier II: Alchimie Émotionnelle (Jour 6-9)',
                    'Pilier III: Expansion et Manifestation (Jour 10-14)',
                    'Chef de cuisine privé',
                    'Accompagnement TAR personnalisé',
                    'Soins sur mesure',
                    'Support prioritaire 24/7',
                ]),
                'tags' => json_encode([
                    'Premium',
                    'Avancé',
                    '3 piliers',
                    'Accompagnement complet',
                    'Chef privé',
                    'Support 24/7',
                ]),
                'services' => json_encode([
                    'Méditations guidées',
                    'Journal de gratitude',
                    'Affirmations quotidiennes',
                    'Espace ZEN',
                    'Confort matériel premium',
                    'Pilier I: Libération du Passé',
                    'Pilier II: Alchimie Émotionnelle',
                    'Pilier III: Expansion et Manifestation',
                    'Chef de cuisine privé',
                    'Accompagnement TAR',
                    'Soins personnalisés',
                    'Support prioritaire 24/7',
                ]),
                'status' => 'available',
                'price' => 5000,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        foreach ($retreatPlans as $plan) {
            DB::table('retreat_plans')->insert($plan);
        }
    }
}
