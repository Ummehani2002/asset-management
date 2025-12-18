<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Asset;
use App\Models\PreventiveMaintenance;
use Carbon\Carbon;

class PreventiveMaintenanceController extends Controller
{
    public function create()
    {
        $assets = Asset::with('assetCategory')->get();
        return view('preventive-maintenance.create', compact('assets'));
    }

     public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'maintenance_date' => 'required|date',
            'next_maintenance_date' => 'nullable|date',
            'maintenance_description' => 'nullable|string',
        ]);

        if (empty($validated['next_maintenance_date'])) {
            $validated['next_maintenance_date'] = Carbon::parse($validated['maintenance_date'])->addDays(90)->toDateString();
        }

        PreventiveMaintenance::create($validated);

        return redirect()->route('preventive-maintenance.create')->with('success', 'Maintenance scheduled.');
    }


    public function index()
    {
        $maintenances = PreventiveMaintenance::with('asset')
            ->orderByDesc('created_at')
            ->paginate(20);
        return view('preventive-maintenance.index', compact('maintenances'));
    }

    // Get asset details via AJAX
    public function getAssetDetails($id)
    {
        $asset = Asset::with('assetCategory')->find($id);

        if (!$asset) {
            return response()->json(['error' => 'Asset not found'], 404);
        }
        return response()->json([
            'id' => $asset->id,
            'asset_code' => $asset->asset_id,
            'serial_number' => $asset->serial_number,
            'category' => $asset->assetCategory->category_name ?? 'N/A',
            'brand' => $asset->brand ?? 'N/A',
        ]);
    }
}