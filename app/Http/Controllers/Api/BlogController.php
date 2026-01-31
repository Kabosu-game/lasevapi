<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BlogController extends Controller
{
    // Lister tous les blogs avec leurs images et auteur (pour l'app Flutter)
    public function index(Request $request)
    {
        $query = Blog::with(['media', 'author']);
        
        // Filtrer par catégorie si spécifiée (pour "Le pouvoir secret")
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        
        $blogs = $query->latest()->get();
        return response()->json($blogs);
    }

    // Afficher un blog
    public function show($id)
    {
        $blog = Blog::with(['media', 'author'])->findOrFail($id);
        return response()->json($blog);
    }

    // Créer un blog avec une galerie d'images
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blogs,slug',
            'description' => 'nullable|string',
            'body' => 'required|string',
            'author_id' => 'required|integer|exists:users,id',
            'is_premium' => 'boolean',
            'images' => 'required|array|min:1',
            'images.*.title' => 'required|string|max:255',
            'images.*.slug' => 'nullable|string|max:255|unique:media,slug',
            'images.*.file_path' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        $blog = Blog::create($request->only(['title', 'slug', 'description', 'body', 'author_id', 'is_premium']));
        foreach ($request->images as $img) {
            $media = new Media([
                'title' => $img['title'],
                'slug' => $img['slug'] ?? null,
                'media_type' => 'image',
                'file_path' => $img['file_path'],
            ]);
            $blog->media()->save($media);
        }
        $blog->load('media');
        return response()->json(['success' => true, 'data' => $blog], 201);
    }

    // Mettre à jour un blog (sans gestion fine de la galerie ici)
    public function update(Request $request, $id)
    {
        $blog = Blog::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blogs,slug,' . $id,
            'description' => 'nullable|string',
            'body' => 'sometimes|required|string',
            'is_premium' => 'boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        $blog->update($request->only(['title', 'slug', 'description', 'body', 'is_premium']));
        $blog->load('media');
        return response()->json(['success' => true, 'data' => $blog]);
    }

    // Supprimer un blog et ses images
    public function destroy($id)
    {
        $blog = Blog::findOrFail($id);
        foreach ($blog->media as $media) {
            $media->delete();
        }
        $blog->delete();
        return response()->json(['success' => true, 'message' => 'Blog supprimé']);
    }
}
