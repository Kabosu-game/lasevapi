<?php

namespace Database\Seeders;

use App\Models\Dish;
use Illuminate\Database\Seeder;

class DishSeeder extends Seeder
{
    public function run(): void
    {
        $dishes = [
            ['name' => 'Salade de quinoa aux légumes de saison', 'sort_order' => 1],
            ['name' => 'Soupe de lentilles corail', 'sort_order' => 2],
            ['name' => 'Curry de légumes et coco', 'sort_order' => 3],
            ['name' => 'Tajine végétarien aux pruneaux', 'sort_order' => 4],
            ['name' => 'Bowl Buddha aux crudités', 'sort_order' => 5],
            ['name' => 'Risotto aux champignons', 'sort_order' => 6],
            ['name' => 'Tarte aux légumes du soleil', 'sort_order' => 7],
            ['name' => 'Dahl de lentilles et épices', 'sort_order' => 8],
            ['name' => 'Compote de fruits maison', 'sort_order' => 9],
        ];

        foreach ($dishes as $data) {
            Dish::firstOrCreate(
                ['name' => $data['name']],
                ['sort_order' => $data['sort_order']]
            );
        }
    }
}
