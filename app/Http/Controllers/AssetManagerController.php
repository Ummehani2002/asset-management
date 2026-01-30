<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\Employee;
use Illuminate\Http\Request;

class AssetManagerController extends Controller
{
    public function index()
    {
        $entities = Entity::with('assetManager')->orderBy('name')->get();
        return view('asset-manager.index', compact('entities'));
    }

    public function edit($id)
    {
        $entity = Entity::findOrFail($id);
        $employees = Employee::orderBy('name')->orderBy('entity_name')->get(['id', 'name', 'entity_name', 'employee_id']);
        return view('asset-manager.edit', compact('entity', 'employees'));
    }

    public function update(Request $request, $id)
    {
        $entity = Entity::findOrFail($id);
        $request->validate([
            'asset_manager_id' => 'nullable|exists:employees,id',
        ]);

        $entity->update([
            'asset_manager_id' => $request->asset_manager_id ?: null,
        ]);

        return redirect()->route('asset-manager.index')->with('success', 'Asset manager updated for ' . ucwords($entity->name) . '.');
    }
}
