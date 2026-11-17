<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ritual;
use App\Models\RitualCategory;
use Illuminate\Http\Request;

/** ESPACE SACRÉ DES LOIS — bibliothèque de rituels (contenu public, saisi dans l'admin). */
class RitualController extends Controller
{
    private const DETAIL_FIELDS = ['objectives', 'materials', 'steps', 'precautions', 'traditions'];

    /** GET /rituals/categories */
    public function categories()
    {
        $categories = RitualCategory::where('is_active', true)
            ->withCount(['rituals' => fn ($q) => $q->where('is_published', true)])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json(['success' => true, 'data' => $categories]);
    }

    /** GET /rituals?category=plantes&q=sauge */
    public function index(Request $request)
    {
        $request->validate([
            'category' => 'nullable|string|max:60',
            'q' => 'nullable|string|max:100',
        ]);

        $query = Ritual::with('category:id,slug,name,icon')
            ->where('is_published', true)
            ->whereHas('category', fn ($q) => $q->where('is_active', true));

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->query('category')));
        }
        if ($request->filled('q')) {
            $term = '%' . trim($request->query('q')) . '%';
            $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('summary', 'like', $term));
        }

        $rituals = $query->orderBy('sort_order')->orderByDesc('id')->get()->makeHidden(self::DETAIL_FIELDS);

        return response()->json(['success' => true, 'data' => $rituals]);
    }

    /** GET /rituals/{ritual} */
    public function show(Ritual $ritual)
    {
        $ritual->load('category:id,slug,name,icon,is_active');
        abort_unless($ritual->is_published && $ritual->category?->is_active, 404);

        return response()->json(['success' => true, 'data' => $ritual]);
    }
}
