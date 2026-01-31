<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chef;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ChefController extends Controller
{
    public function index()
    {
        $chefs = Chef::orderBy('sort_order')->orderBy('name')->paginate(20);
        return view('admin.chefs.index', compact('chefs'));
    }

    public function create()
    {
        return view('admin.chefs.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'role' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $data = $request->only('name', 'role', 'sort_order');
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('chefs', 'public');
        }
        Chef::create($data);
        return redirect()->route('admin.chefs.index')->with('success', 'Chef ajouté avec succès.');
    }

    public function edit($id)
    {
        $chef = Chef::findOrFail($id);
        return view('admin.chefs.edit', compact('chef'));
    }

    public function update(Request $request, $id)
    {
        $chef = Chef::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'role' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $data = $request->only('name', 'role', 'sort_order');
        if ($request->hasFile('image')) {
            if ($chef->image) {
                Storage::disk('public')->delete($chef->image);
            }
            $data['image'] = $request->file('image')->store('chefs', 'public');
        }
        $chef->update($data);
        return redirect()->route('admin.chefs.index')->with('success', 'Chef mis à jour.');
    }

    public function destroy($id)
    {
        $chef = Chef::findOrFail($id);
        if ($chef->image) {
            Storage::disk('public')->delete($chef->image);
        }
        $chef->delete();
        return redirect()->route('admin.chefs.index')->with('success', 'Chef supprimé.');
    }
}
