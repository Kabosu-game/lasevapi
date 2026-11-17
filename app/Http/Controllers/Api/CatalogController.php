<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityCategory;
use App\Models\BreathingPattern;

/** Listes de référence de l'app, gérées dans l'admin. */
class CatalogController extends Controller
{
    /** GET /activity-categories : catégories visibles de l'espace Activités. */
    public function activityCategories()
    {
        $categories = ActivityCategory::where('is_active', true)->ordered()
            ->get(['key', 'name', 'description', 'icon', 'color', 'with_breathing_exercises', 'sort_order']);

        return response()->json(['success' => true, 'data' => $categories]);
    }

    /** GET /breathing-patterns : exercices de respiration guidés. */
    public function breathingPatterns()
    {
        $patterns = BreathingPattern::where('is_active', true)->ordered()
            ->get(['id', 'name', 'description', 'inhale_seconds', 'hold_in_seconds', 'exhale_seconds', 'hold_out_seconds', 'sort_order']);

        return response()->json(['success' => true, 'data' => $patterns]);
    }
}
