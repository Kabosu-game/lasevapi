<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::with('media')->latest()->paginate(15);
        return view('admin.events.index', compact('events'));
    }

    public function create()
    {
        return view('admin.events.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'event_date' => 'required|date',
            'location' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'current_participants' => 'nullable|integer|min:0',
            'status' => 'required|in:upcoming,ongoing,completed,cancelled',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ], [
            'gallery_images.*.image' => 'Chaque fichier de la galerie doit être une image.',
            'gallery_images.*.max' => 'Chaque image ne doit pas dépasser 5 Mo.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $event = Event::create($request->only([
            'title', 'description', 'event_date', 'location',
            'price', 'current_participants', 'status',
        ]));

        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $file) {
                $path = $file->store('events/gallery', 'public');
                $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $event->media()->create([
                    'title' => $title,
                    'slug' => Str::slug($title) . '-' . uniqid(),
                    'media_type' => 'image',
                    'file_path' => $path,
                ]);
            }
        }

        return redirect()->route('admin.events.index')
            ->with('success', 'Événement créé avec succès.');
    }

    public function show($id)
    {
        $event = Event::with('media')->findOrFail($id);
        return view('admin.events.show', compact('event'));
    }

    public function edit($id)
    {
        $event = Event::with('media')->findOrFail($id);
        return view('admin.events.edit', compact('event'));
    }

    public function update(Request $request, $id)
    {
        $event = Event::with('media')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'event_date' => 'required|date',
            'location' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'current_participants' => 'nullable|integer|min:0',
            'status' => 'required|in:upcoming,ongoing,completed,cancelled',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'delete_media' => 'nullable|array',
            'delete_media.*' => 'integer|exists:media,id',
        ], [
            'gallery_images.*.image' => 'Chaque fichier de la galerie doit être une image.',
            'gallery_images.*.max' => 'Chaque image ne doit pas dépasser 5 Mo.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $event->update($request->only([
            'title', 'description', 'event_date', 'location',
            'price', 'current_participants', 'status',
        ]));

        if ($request->filled('delete_media')) {
            foreach ($event->media()->whereIn('id', $request->delete_media)->get() as $media) {
                Storage::disk('public')->delete($media->file_path);
                $media->delete();
            }
        }

        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $file) {
                $path = $file->store('events/gallery', 'public');
                $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $event->media()->create([
                    'title' => $title,
                    'slug' => Str::slug($title) . '-' . uniqid(),
                    'media_type' => 'image',
                    'file_path' => $path,
                ]);
            }
        }

        return redirect()->route('admin.events.index')
            ->with('success', 'Événement mis à jour avec succès.');
    }

    public function destroy($id)
    {
        $event = Event::with('media')->findOrFail($id);
        foreach ($event->media as $media) {
            Storage::disk('public')->delete($media->file_path);
            $media->delete();
        }
        $event->delete();

        return redirect()->route('admin.events.index')
            ->with('success', 'Événement supprimé avec succès.');
    }
}

