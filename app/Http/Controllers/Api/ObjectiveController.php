<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Objective;

class ObjectiveController extends Controller
{
    /**
     * Récupérer tous les objectifs disponibles (ordre défini dans l'admin)
     */
    public function index()
    {
        $objectives = Objective::orderBy('sort_order')->orderBy('id')->get();
        return response()->json($objectives);
    }
}
