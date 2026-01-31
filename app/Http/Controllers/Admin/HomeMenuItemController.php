<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeMenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class HomeMenuItemController extends Controller
{
    public function index()
    {
        $items = HomeMenuItem::orderBy('sort_order')->get();
        return view('admin.home-menu-items.index', compact('items'));
    }

    public function edit($id)
    {
        $item = HomeMenuItem::findOrFail($id);
        return view('admin.home-menu-items.edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = HomeMenuItem::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $item->name = $request->name;
        if ($request->boolean('remove_image')) {
            if ($item->image) {
                Storage::disk('public')->delete($item->image);
            }
            $item->image = null;
        } elseif ($request->hasFile('image')) {
            if ($item->image) {
                Storage::disk('public')->delete($item->image);
            }
            $item->image = $request->file('image')->store('home-menu', 'public');
        }
        $item->save();
        return redirect()->route('admin.home-menu-items.index')
            ->with('success', 'Menu mis à jour.');
    }
}
