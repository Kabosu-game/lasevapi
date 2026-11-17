<?php

namespace Database\Seeders;

use App\Models\Objective;
use Illuminate\Database\Seeder;

class ObjectiveSeeder extends Seeder
{
    /**
     * Objectifs proposés à l'inscription (modifiables ensuite dans l'admin).
     */
    public function run(): void
    {
        $objectives = [
            'Paix intérieure',
            'Réduction du stress',
            'Meilleur sommeil',
            'Concentration',
            'Énergie vitale',
            'Créativité',
            'Confiance en soi',
            'Relations harmonieuses',
            'Santé globale',
            'Éveil spirituel',
        ];

        foreach ($objectives as $i => $name) {
            Objective::firstOrCreate(['name' => $name], ['sort_order' => $i + 1]);
        }
    }
}
