<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dish;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DishController extends Controller
{
    public function index()
    {
        $dishes = Dish::orderBy('sort_order')->orderBy('name')->paginate(20);
        return view('admin.dishes.index', compact('dishes'));
    }

    public function create()
    {
        return view('admin.dishes.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $data = $request->only('name', 'sort_order');
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('dishes', 'public');
        }
        Dish::create($data);
        return redirect()->route('admin.dishes.index')->with('success', 'Plat ajouté avec succès.');
    }

    public function edit($id)
    {
        $dish = Dish::findOrFail($id);
        return view('admin.dishes.edit', compact('dish'));
    }

    public function update(Request $request, $id)
    {
        $dish = Dish::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $data = $request->only('name', 'sort_order');
        if ($request->hasFile('image')) {
            if ($dish->image) {
                Storage::disk('public')->delete($dish->image);
            }
            $data['image'] = $request->file('image')->store('dishes', 'public');
        }
        $dish->update($data);
        return redirect()->route('admin.dishes.index')->with('success', 'Plat mis à jour.');
    }

    public function destroy($id)
    {
        $dish = Dish::findOrFail($id);
        if ($dish->image) {
            Storage::disk('public')->delete($dish->image);
        }
        $dish->delete();
        return redirect()->route('admin.dishes.index')->with('success', 'Plat supprimé.');
    }
}
