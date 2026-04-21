<?php

namespace App\Http\Controllers;

use App\Models\NasStorage;
use Illuminate\Http\Request;

class NasStorageController extends Controller
{
    public function index()
    {
        $items = NasStorage::latest()->get();
        return view('nas-storage.index', compact('items'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'ip_address' => 'required|string|max:100',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ]);

        NasStorage::create($validated);

        return redirect()
            ->route('nas-storage.index')
            ->with('success', 'NAS Storage record created successfully.');
    }

    public function edit($id)
    {
        $item = NasStorage::findOrFail($id);
        return view('nas-storage.edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = NasStorage::findOrFail($id);

        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'ip_address' => 'required|string|max:100',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ]);

        $item->update($validated);

        return redirect()
            ->route('nas-storage.index')
            ->with('success', 'NAS Storage record updated successfully.');
    }

    public function destroy($id)
    {
        $item = NasStorage::findOrFail($id);
        $item->delete();

        return redirect()
            ->route('nas-storage.index')
            ->with('success', 'NAS Storage record deleted successfully.');
    }
}
