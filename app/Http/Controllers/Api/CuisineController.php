<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dish;
use App\Models\Chef;

class CuisineController extends Controller
{
    /**
     * Liste des plats (affichage app Cuisine)
     */
    public function dishes()
    {
        $dishes = Dish::orderBy('sort_order')->orderBy('name')->get();
        return response()->json($dishes);
    }

    /**
     * Liste des chefs cuisiniers (affichage app Cuisine)
     */
    public function chefs()
    {
        $chefs = Chef::orderBy('sort_order')->orderBy('name')->get();
        return response()->json($chefs);
    }
}
