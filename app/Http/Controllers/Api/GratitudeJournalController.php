<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GratitudeJournal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GratitudeJournalController extends Controller
{
    // Lister les journaux de gratitude pour un utilisateur donné (index)
    public function index(Request $request)
    {
        // Récupérer user_id depuis la requête (query param ou auth user)
        $userId = $request->input('user_id') ?? $request->user()?->id;
        
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'User ID requis'], 422);
        }
        
        $journals = GratitudeJournal::where('user_id', intval($userId))
            ->orderBy('journal_date', 'desc')
            ->get();
        return response()->json($journals);
    }

    // Afficher un journal de gratitude
    public function show(Request $request, $id)
    {
        $journal = GratitudeJournal::findOrFail($id);
        
        // Vérifier que l'utilisateur peut voir ce journal (soit c'est le sien, soit admin)
        $userId = $request->user()?->id;
        if ($userId && $journal->user_id != $userId && $request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }
        
        return response()->json($journal);
    }

    // Créer un journal de gratitude
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'title' => 'nullable|string|max:255',
            'positive_thing_1' => 'required|string',
            'positive_thing_2' => 'nullable|string',
            'positive_thing_3' => 'nullable|string',
            'journal_date' => 'required|date',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        $journal = GratitudeJournal::create($request->only([
            'user_id',
            'title',
            'positive_thing_1',
            'positive_thing_2',
            'positive_thing_3',
            'journal_date',
        ]));
        return response()->json(['success' => true, 'data' => $journal], 201);
    }

    // Mettre à jour un journal de gratitude
    public function update(Request $request, $id)
    {
        $journal = GratitudeJournal::findOrFail($id);
        
        // Vérifier que l'utilisateur peut modifier ce journal
        $userId = $request->user()?->id;
        if ($userId && $journal->user_id != $userId && $request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'positive_thing_1' => 'sometimes|required|string',
            'positive_thing_2' => 'sometimes|required|string',
            'positive_thing_3' => 'sometimes|required|string',
            'journal_date' => 'sometimes|required|date',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        
        $journal->update($request->only([
            'title',
            'positive_thing_1',
            'positive_thing_2',
            'positive_thing_3',
            'journal_date',
        ]));
        
        return response()->json(['success' => true, 'data' => $journal]);
    }

    // Supprimer un journal de gratitude
    public function destroy(Request $request, $id)
    {
        $journal = GratitudeJournal::findOrFail($id);
        
        // Vérifier que l'utilisateur peut supprimer ce journal
        $userId = $request->user()?->id;
        if ($userId && $journal->user_id != $userId && $request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }
        
        $journal->delete();
        return response()->json(['success' => true, 'message' => 'Journal supprimé']);
    }

    /**
     * Afficher les journaux de gratitude d'un utilisateur
     */
  
}
