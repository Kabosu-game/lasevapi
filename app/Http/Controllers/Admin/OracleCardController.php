<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OracleCard;
use App\Models\OracleDraw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OracleCardController extends Controller
{
    public function index()
    {
        $cards = OracleCard::withCount('draws')->orderBy('name')->paginate(30);
        $stats = [
            'active' => OracleCard::where('is_active', true)->count(),
            'draws_month' => OracleDraw::where('cycle', now()->format('Y-m'))->count(),
            'users_month' => OracleDraw::where('cycle', now()->format('Y-m'))->distinct('user_id')->count('user_id'),
        ];
        return view('admin.oracle-cards.index', compact('cards', 'stats'));
    }

    public function create()
    {
        return view('admin.oracle-cards.form', ['card' => new OracleCard(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('oracle', 'public');
        }
        OracleCard::create($data);
        return redirect()->route('admin.oracle-cards.index')->with('success', 'Carte créée.');
    }

    public function edit(OracleCard $oracleCard)
    {
        return view('admin.oracle-cards.form', ['card' => $oracleCard]);
    }

    public function update(Request $request, OracleCard $oracleCard)
    {
        $data = $this->validated($request);
        if ($request->hasFile('image')) {
            if ($oracleCard->image && !str_starts_with($oracleCard->image, 'http')) {
                Storage::disk('public')->delete($oracleCard->image);
            }
            $data['image'] = $request->file('image')->store('oracle', 'public');
        }
        $oracleCard->update($data);
        return redirect()->route('admin.oracle-cards.index')->with('success', 'Carte mise à jour.');
    }

    public function destroy(OracleCard $oracleCard)
    {
        if ($oracleCard->draws()->exists()) {
            // Conserver l'historique des utilisateurs : on désactive au lieu de supprimer.
            $oracleCard->update(['is_active' => false]);
            return back()->with('success', 'Cette carte a déjà été tirée : elle a été désactivée pour conserver l\'historique.');
        }
        if ($oracleCard->image && !str_starts_with($oracleCard->image, 'http')) {
            Storage::disk('public')->delete($oracleCard->image);
        }
        $oracleCard->delete();
        return back()->with('success', 'Carte supprimée.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'message' => 'required|string|max:500',
            'interpretation' => 'required|string|max:5000',
            'keywords' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:5120',
        ]);
        unset($data['image']);
        $data['is_active'] = $request->boolean('is_active');
        return $data;
    }
}
