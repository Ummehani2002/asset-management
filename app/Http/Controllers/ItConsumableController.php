<?php

namespace App\Http\Controllers;

use App\Models\ItConsumable;
use Illuminate\Http\Request;

class ItConsumableController extends Controller
{
    public function index()
    {
        $items = ItConsumable::latest()->get();
        return view('it-consumables.index', compact('items'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_no' => 'required|string|max:100|unique:it_consumables,id_no',
            'item_description' => 'required|string|max:500',
            'issued_date' => 'required|date',
            'remarks' => 'nullable|string|max:1000',
        ]);

        ItConsumable::create($validated);

        return redirect()
            ->route('it-consumables.index')
            ->with('success', 'IT Consumable created successfully.');
    }

    public function edit($id)
    {
        $item = ItConsumable::findOrFail($id);
        return view('it-consumables.edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = ItConsumable::findOrFail($id);

        $validated = $request->validate([
            'id_no' => 'required|string|max:100|unique:it_consumables,id_no,' . $item->id,
            'item_description' => 'required|string|max:500',
            'issued_date' => 'required|date',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $item->update($validated);

        return redirect()
            ->route('it-consumables.index')
            ->with('success', 'IT Consumable updated successfully.');
    }

    public function destroy($id)
    {
        $item = ItConsumable::findOrFail($id);
        $item->delete();

        return redirect()
            ->route('it-consumables.index')
            ->with('success', 'IT Consumable deleted successfully.');
    }
}
