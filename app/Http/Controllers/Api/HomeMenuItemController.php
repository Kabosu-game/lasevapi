<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomeMenuItem;

class HomeMenuItemController extends Controller
{
    /**
     * Liste des menus de la page d'accueil (nom + image par section)
     */
    public function index()
    {
        $items = HomeMenuItem::orderBy('sort_order')->get();
        return response()->json($items);
    }
}
