<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Accueil : « Face à toi » (7 Portes Intérieures) et « Espace Sacré des Lois ». */
return new class extends Migration
{
    private array $items = [
        ['slug' => 'inner-gates', 'name' => 'Face à toi', 'sort_order' => 7],
        ['slug' => 'sacred-space', 'name' => 'Espace Sacré des Lois', 'sort_order' => 8],
    ];

    public function up(): void
    {
        $now = now();
        foreach ($this->items as $item) {
            if (!DB::table('home_menu_items')->where('slug', $item['slug'])->exists()) {
                DB::table('home_menu_items')->insert($item + ['created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        DB::table('home_menu_items')->whereIn('slug', array_column($this->items, 'slug'))->delete();
    }
};
