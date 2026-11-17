<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    // Lister tous les événements avec leurs images
    public function index()
    {
        $events = Event::with('media')->get();
        return response()->json($events);
    }

    // Afficher un événement
    public function show($id)
    {
        $event = Event::with('media')->findOrFail($id);
        return response()->json($event);
    }

    /** POST /events/{event}/register : inscription depuis l'app (prénom, nom, téléphone). */
    public function register(Request $request, Event $event)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => ['required', 'string', 'max:40', 'regex:/^[0-9+().\s-]{6,40}$/'],
        ], [
            'phone.regex' => 'Le numéro de téléphone n\'est pas valide.',
        ]);

        if (!$event->acceptsRegistrations()) {
            return response()->json([
                'success' => false,
                'message' => 'Les inscriptions sont fermées pour cet événement.',
            ], 409);
        }

        $phone = trim($data['phone']);
        if ($event->registrations()->where('phone', $phone)->exists()) {
            return response()->json([
                'success' => true,
                'already_registered' => true,
                'message' => 'Tu es déjà inscrit(e) à cet événement.',
            ]);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($event, $data, $phone) {
            $event->registrations()->create([
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'phone' => $phone,
            ]);
            $event->increment('current_participants');
        });

        return response()->json([
            'success' => true,
            'already_registered' => false,
            'message' => 'Inscription enregistrée. L\'équipe te contactera bientôt.',
            'participants' => $event->fresh()->current_participants,
        ], 201);
    }

    // Créer un événement avec une galerie d'images
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date' => 'required|date',
            'location' => 'nullable|string|max:255',
            'price' => 'nullable|numeric',
            'status' => 'nullable|in:upcoming,ongoing,completed,cancelled',
            'images' => 'required|array|min:1',
            'images.*.title' => 'required|string|max:255',
            'images.*.slug' => 'nullable|string|max:255|unique:media,slug',
            'images.*.file_path' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        $event = Event::create($request->only(['title', 'description', 'event_date', 'location', 'price', 'status']));
        foreach ($request->images as $img) {
            $media = new Media([
                'title' => $img['title'],
                'slug' => $img['slug'] ?? null,
                'media_type' => 'image',
                'file_path' => $img['file_path'],
            ]);
            $event->media()->save($media);
        }
        $event->load('media');
        return response()->json(['success' => true, 'data' => $event], 201);
    }

    // Mettre à jour un événement (sans gestion fine de la galerie ici)
    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'event_date' => 'sometimes|required|date',
            'location' => 'nullable|string|max:255',
            'price' => 'nullable|numeric',
            'status' => 'nullable|in:upcoming,ongoing,completed,cancelled',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        $event->update($request->only(['title', 'description', 'event_date', 'location', 'price', 'status']));
        $event->load('media');
        return response()->json(['success' => true, 'data' => $event]);
    }

    // Supprimer un événement et ses images
    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        foreach ($event->media as $media) {
            $media->delete();
        }
        $event->delete();
        return response()->json(['success' => true, 'message' => 'Événement supprimé']);
    }
}
