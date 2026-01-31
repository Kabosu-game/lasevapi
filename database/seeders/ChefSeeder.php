<?php

namespace Database\Seeders;

use App\Models\Chef;
use Illuminate\Database\Seeder;

class ChefSeeder extends Seeder
{
    public function run(): void
    {
        $chefs = [
            ['name' => 'Marie Dupont', 'role' => 'Cheffe exécutive', 'sort_order' => 1],
            ['name' => 'Jean Martin', 'role' => 'Chef pâtissier', 'sort_order' => 2],
            ['name' => 'Sophie Bernard', 'role' => 'Cheffe cuisine végétale', 'sort_order' => 3],
            ['name' => 'Pierre Lefebvre', 'role' => 'Chef traiteur', 'sort_order' => 4],
            ['name' => 'Claire Moreau', 'role' => 'Cheffe boulangère', 'sort_order' => 5],
            ['name' => 'Thomas Petit', 'role' => 'Chef cuisine santé', 'sort_order' => 6],
            ['name' => 'Isabelle Laurent', 'role' => 'Cheffe des desserts', 'sort_order' => 7],
            ['name' => 'Nicolas Simon', 'role' => 'Chef cuisine ayurvédique', 'sort_order' => 8],
            ['name' => 'Émilie Roux', 'role' => 'Cheffe cuisine bio', 'sort_order' => 9],
        ];

        foreach ($chefs as $data) {
            Chef::firstOrCreate(
                ['name' => $data['name']],
                [
                    'role' => $data['role'],
                    'sort_order' => $data['sort_order'],
                ]
            );
        }
    }
}
