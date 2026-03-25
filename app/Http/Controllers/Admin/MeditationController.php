<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Meditation;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MeditationController extends Controller
{
    /**
     * Liste des méditations
     */
    public function index()
    {
        $meditations = Meditation::with('media')->latest()->paginate(15);
        return view('admin.meditations.index', compact('meditations'));
    }

    /**
     * Formulaire de création
     */
    public function create()
    {
        return view('admin.meditations.create');
    }

    /**
     * Créer une méditation
     */
    public function store(Request $request)
    {
        $uploadedFile = $request->file('media_file');

        // Détecter l'échec d'upload PHP (fichier trop gros, post_max_size, etc.) AVANT la validation
        if ($request->isMethod('post') && $request->hasAny(['media_title', 'title'])) {
            if (!$uploadedFile) {
                return back()->withInput()->with('error',
                    'Aucun fichier reçu. Vérifiez : 1) Le formulaire a bien enctype="multipart/form-data". ' .
                    '2) Le fichier ne dépasse pas la limite PHP (php.ini : upload_max_filesize et post_max_size à 500M ou plus).'
                );
            }
            if (!$uploadedFile->isValid()) {
                $err = $uploadedFile->getError();
                $messages = [
                    \UPLOAD_ERR_INI_SIZE => 'Fichier trop volumineux : limite PHP (upload_max_filesize) dépassée. Augmentez-la dans php.ini (ex. 500M) puis redémarrez Apache.',
                    \UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale autorisée par le formulaire.',
                    \UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement envoyé. Réessayez.',
                    \UPLOAD_ERR_NO_FILE => 'Aucun fichier reçu.',
                    \UPLOAD_ERR_NO_TMP_DIR => 'Erreur serveur : dossier temporaire manquant.',
                    \UPLOAD_ERR_CANT_WRITE => 'Erreur serveur : impossible d\'écrire le fichier sur le disque.',
                    \UPLOAD_ERR_EXTENSION => 'Une extension PHP a bloqué l\'upload.',
                ];
                $msg = $messages[$err] ?? 'Erreur d\'upload (code ' . $err . ').';
                return back()->withInput()->with('error', $msg);
            }
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:meditations,slug',
            'description' => 'nullable|string',
            'duration' => 'nullable|integer',
            'media_title' => 'required|string|max:255',
            'media_slug' => 'nullable|string|max:255|unique:media,slug',
            'media_file' => 'required|file|mimes:mp3,mp4,m4a,mov,avi,wav,ogg,oga|max:512000', // 500MB max (pour viser >30 min selon bitrate)
            'media_type' => 'required|in:audio,video',
            'media_duration' => 'nullable|integer',
        ], [
            'media_file.required' => 'Veuillez sélectionner un fichier audio ou vidéo.',
            'media_file.file' => 'Le fichier média n\'a pas pu être reçu. Vérifiez enctype="multipart/form-data" et les limites PHP (upload_max_filesize, post_max_size).',
                    'media_file.uploaded' => 'L\'upload du fichier a échoué. Vérifiez la taille (max 500 Mo) et les limites dans php.ini.',
            'media_file.mimes' => 'Formats acceptés : mp3, mp4, m4a, mov, avi, wav, ogg, oga.',
            'media_file.max' => 'Le fichier ne doit pas dépasser 500 Mo. Augmentez upload_max_filesize et post_max_size dans php.ini (500M).',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $file = $request->file('media_file');
        $mediaType = $request->media_type;

        try {
            $directory = $mediaType === 'video' ? 'videos/meditations' : 'audios/meditations';
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $storedPath = $file->storeAs($directory, $fileName, 'public');
            $filePath = 'storage/' . $storedPath;
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Le fichier média n\'a pas pu être enregistré : ' . $e->getMessage());
        }

        $meditation = Meditation::create($request->only(['title', 'slug', 'description', 'duration']));

        $media = new Media([
            'title' => $request->media_title,
            'slug' => $request->media_slug,
            'media_type' => $mediaType,
            'file_path' => $filePath,
            'duration' => $request->media_duration,
        ]);
        $meditation->media()->save($media);

        return redirect()->route('admin.meditations.index')
            ->with('success', 'Méditation créée avec succès');
    }

    /**
     * Afficher une méditation
     */
    public function show(Meditation $meditation)
    {
        $meditation->load('media');
        return view('admin.meditations.show', compact('meditation'));
    }

    /**
     * Formulaire d'édition
     */
    public function edit(Meditation $meditation)
    {
        $meditation->load('media');
        return view('admin.meditations.edit', compact('meditation'));
    }

    /**
     * Mettre à jour une méditation
     */
    public function update(Request $request, Meditation $meditation)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:meditations,slug,' . $meditation->id,
            'description' => 'nullable|string',
            'duration' => 'nullable|integer',
            'media_title' => 'sometimes|required|string|max:255',
            'media_slug' => 'nullable|string|max:255|unique:media,slug,' . ($meditation->media->id ?? 'NULL'),
            'media_file' => 'sometimes|file|mimes:mp3,mp4,m4a,mov,avi,wav,ogg,oga|max:512000', // 500MB max
            'media_type' => 'sometimes|required|in:audio,video',
            'media_duration' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $meditation->update($request->only(['title', 'slug', 'description', 'duration']));

        if ($meditation->media) {
            $updateData = [
                'title' => $request->media_title ?? $meditation->media->title,
                'slug' => $request->media_slug ?? $meditation->media->slug,
                'duration' => $request->media_duration ?? $meditation->media->duration,
            ];

            if ($request->hasFile('media_file')) {
                $file = $request->file('media_file');
                $mediaType = $request->media_type ?? $meditation->media->media_type;

                try {
                    $oldPath = $meditation->media->file_path;
                    if ($oldPath && str_starts_with($oldPath, 'storage/')) {
                        $storagePath = str_replace('storage/', '', $oldPath);
                        if (Storage::disk('public')->exists($storagePath)) {
                            Storage::disk('public')->delete($storagePath);
                        }
                    } elseif ($oldPath && file_exists(public_path($oldPath))) {
                        @unlink(public_path($oldPath));
                    }

                    $directory = $mediaType === 'video' ? 'videos/meditations' : 'audios/meditations';
                    $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
                    $storedPath = $file->storeAs($directory, $fileName, 'public');
                    $updateData['file_path'] = 'storage/' . $storedPath;
                    $updateData['media_type'] = $mediaType;
                } catch (\Throwable $e) {
                    return back()
                        ->withInput()
                        ->with('error', 'Le fichier média n\'a pas pu être enregistré : ' . $e->getMessage());
                }
            }

            $meditation->media->update($updateData);
        }

        return redirect()->route('admin.meditations.index')
            ->with('success', 'Méditation mise à jour avec succès');
    }

    /**
     * Supprimer une méditation
     */
    public function destroy(Meditation $meditation)
    {
        if ($meditation->media) {
            $meditation->media->delete();
        }
        $meditation->delete();

        return redirect()->route('admin.meditations.index')
            ->with('success', 'Méditation supprimée avec succès');
    }
}

