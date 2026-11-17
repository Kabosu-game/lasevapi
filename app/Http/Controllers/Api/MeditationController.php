<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meditation;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MeditationController extends Controller
{
    // Lister toutes les méditations
    public function index(Request $request)
    {
        $query = Meditation::with('media');
        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }
        $meditations = $query->get();
        return response()->json($meditations);
    }

    // Afficher une méditation
    public function show($id)
    {
        $meditation = Meditation::with('media')->findOrFail($id);
        return response()->json($meditation);
    }

    // Créer une méditation avec un media audio
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:meditations,slug',
            'description' => 'nullable|string',
            'duration' => 'nullable|integer',
            'media_title' => 'required|string|max:255',
            'media_slug' => 'nullable|string|max:255|unique:media,slug',
            'file_path' => 'required|string',
            'media_duration' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $meditation = Meditation::create($request->only(['title', 'slug', 'description', 'duration']));

        $media = new Media([
            'title' => $request->media_title,
            'slug' => $request->media_slug,
            'media_type' => 'audio',
            'file_path' => $request->file_path,
            'duration' => $request->media_duration,
        ]);
        $meditation->media()->save($media);

        $meditation->load('media');
        return response()->json(['success' => true, 'data' => $meditation], 201);
    }

    // Mettre à jour une méditation et son media audio
    public function update(Request $request, $id)
    {
        $meditation = Meditation::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:meditations,slug,' . $id,
            'description' => 'nullable|string',
            'duration' => 'nullable|integer',
            'media_title' => 'sometimes|required|string|max:255',
            'media_slug' => 'nullable|string|max:255|unique:media,slug,' . ($meditation->media->id ?? 'NULL'),
            'file_path' => 'sometimes|required|string',
            'media_duration' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        $meditation->update($request->only(['title', 'slug', 'description', 'duration']));
        if ($meditation->media) {
            $meditation->media->update([
                'title' => $request->media_title ?? $meditation->media->title,
                'slug' => $request->media_slug ?? $meditation->media->slug,
                'file_path' => $request->file_path ?? $meditation->media->file_path,
                'duration' => $request->media_duration ?? $meditation->media->duration,
            ]);
        }
        $meditation->load('media');
        return response()->json(['success' => true, 'data' => $meditation]);
    }

    // Supprimer une méditation et son media
    public function destroy($id)
    {
        $meditation = Meditation::findOrFail($id);
        if ($meditation->media) {
            $meditation->media->delete();
        }
        $meditation->delete();
        return response()->json(['success' => true, 'message' => 'Méditation supprimée']);
    }
}
