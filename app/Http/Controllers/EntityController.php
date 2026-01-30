<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\Http\Request;

class EntityController extends Controller
{
    public function index()
    {
        $entities = Entity::orderBy('name')->get();
        return view('entity-master.index', compact('entities'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:entities,name',
        ]);

        Entity::create(['name' => trim($request->name)]);

        return redirect()->route('entity-master.index')->with('success', 'Entity created successfully.');
    }

    public function edit($id)
    {
        $entity = Entity::findOrFail($id);
        return view('entity-master.edit', compact('entity'));
    }

    public function update(Request $request, $id)
    {
        $entity = Entity::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:100|unique:entities,name,' . $entity->id,
        ]);

        $entity->update(['name' => trim($request->name)]);

        return redirect()->route('entity-master.index')->with('success', 'Entity updated successfully.');
    }

    public function destroy($id)
    {
        $entity = Entity::findOrFail($id);
        $entity->delete();
        return redirect()->route('entity-master.index')->with('success', 'Entity deleted successfully.');
    }
}
