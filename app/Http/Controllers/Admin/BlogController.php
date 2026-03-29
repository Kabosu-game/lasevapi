<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index()
    {
        $blogs = Blog::with('media')->latest()->paginate(15);
        return view('admin.blogs.index', compact('blogs'));
    }

    public function create()
    {
        $authors = User::where('role', 'admin')->get();
        return view('admin.blogs.create', compact('authors'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'body' => 'required|string',
            'author_id' => 'nullable|exists:users,id',
            'is_premium' => 'boolean',
            'category' => 'nullable|string|max:255',
            'cover_image' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:20480',
            'images' => 'nullable|array|max:10',
            'images.*' => 'file|image|mimes:jpg,jpeg,png,webp|max:20480', // 20MB max par image
            'videos' => 'nullable|array|max:5',
            'videos.*' => 'file|mimes:mp4,webm,mov,m4v|max:204800', // ~200 Mo max par vidéo
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $request->all();
        $data['slug'] = Str::slug($request->title);
        $data['author_id'] = $request->author_id ?? auth()->id();
        $data['is_premium'] = $request->has('is_premium');
        $data['category'] = $request->category ?? 'general';

        if ($request->hasFile('cover_image') && $request->file('cover_image')->isValid()) {
            $cover = $request->file('cover_image');
            $fileName = 'cover_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $cover->getClientOriginalName());
            $storedPath = $cover->storeAs('images/blogs/covers', $fileName, 'public');
            $data['cover_image'] = 'storage/' . $storedPath;
        } else {
            unset($data['cover_image']);
        }

        $blog = Blog::create($data);

        $uploadedImages = $request->file('images', []);
        foreach ($uploadedImages as $image) {
            if (!$image || !$image->isValid()) continue;

            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $image->getClientOriginalName());
            $storedPath = $image->storeAs('images/blogs', $fileName, 'public');
            $filePath = 'storage/' . $storedPath;

            $blog->media()->save(new Media([
                'title' => $image->getClientOriginalName(),
                'slug' => null,
                'media_type' => 'image',
                'file_path' => $filePath,
            ]));
        }

        $uploadedVideos = $request->file('videos', []);
        foreach ($uploadedVideos as $video) {
            if (!$video || !$video->isValid()) {
                continue;
            }

            $fileName = 'v_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $video->getClientOriginalName());
            $storedPath = $video->storeAs('videos/blogs', $fileName, 'public');
            $filePath = 'storage/' . $storedPath;

            $blog->media()->save(new Media([
                'title' => $video->getClientOriginalName(),
                'slug' => null,
                'media_type' => 'video',
                'file_path' => $filePath,
            ]));
        }

        return redirect()->route('admin.blogs.index')
            ->with('success', 'Blog créé avec succès.');
    }

    public function show($id)
    {
        $blog = Blog::with(['media', 'author'])->findOrFail($id);
        return view('admin.blogs.show', compact('blog'));
    }

    public function edit($id)
    {
        $blog = Blog::findOrFail($id);
        $authors = User::where('role', 'admin')->get();
        return view('admin.blogs.edit', compact('blog', 'authors'));
    }

    public function update(Request $request, $id)
    {
        $blog = Blog::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'body' => 'required|string',
            'author_id' => 'nullable|exists:users,id',
            'is_premium' => 'boolean',
            'category' => 'nullable|string|max:255',
            'cover_image' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:20480',
            'images' => 'nullable|array|max:10',
            'images.*' => 'file|image|mimes:jpg,jpeg,png,webp|max:20480', // 20MB max par image
            'videos' => 'nullable|array|max:5',
            'videos.*' => 'file|mimes:mp4,webm,mov,m4v|max:204800',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $request->all();
        $data['slug'] = Str::slug($request->title);
        $data['is_premium'] = $request->has('is_premium');
        $data['category'] = $request->category ?? $blog->category ?? 'general';

        if ($request->hasFile('cover_image') && $request->file('cover_image')->isValid()) {
            $cover = $request->file('cover_image');
            $fileName = 'cover_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $cover->getClientOriginalName());
            $storedPath = $cover->storeAs('images/blogs/covers', $fileName, 'public');
            $data['cover_image'] = 'storage/' . $storedPath;
        } else {
            unset($data['cover_image']);
        }

        $blog->update($data);

        if ($request->hasFile('images')) {
            $uploadedImages = $request->file('images', []);
            foreach ($uploadedImages as $image) {
                if (!$image || !$image->isValid()) continue;

                $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $image->getClientOriginalName());
                $storedPath = $image->storeAs('images/blogs', $fileName, 'public');
                $filePath = 'storage/' . $storedPath;

                $blog->media()->save(new Media([
                    'title' => $image->getClientOriginalName(),
                    'slug' => null,
                    'media_type' => 'image',
                    'file_path' => $filePath,
                ]));
            }
        }

        if ($request->hasFile('videos')) {
            $uploadedVideos = $request->file('videos', []);
            foreach ($uploadedVideos as $video) {
                if (!$video || !$video->isValid()) {
                    continue;
                }

                $fileName = 'v_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $video->getClientOriginalName());
                $storedPath = $video->storeAs('videos/blogs', $fileName, 'public');
                $filePath = 'storage/' . $storedPath;

                $blog->media()->save(new Media([
                    'title' => $video->getClientOriginalName(),
                    'slug' => null,
                    'media_type' => 'video',
                    'file_path' => $filePath,
                ]));
            }
        }

        return redirect()->route('admin.blogs.index')
            ->with('success', 'Blog mis à jour avec succès.');
    }

    public function destroy($id)
    {
        $blog = Blog::findOrFail($id);
        foreach ($blog->media as $media) {
            $media->delete();
        }
        $blog->delete();

        return redirect()->route('admin.blogs.index')
            ->with('success', 'Blog supprimé avec succès.');
    }
}

