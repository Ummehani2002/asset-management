<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Asset;
use App\Models\AssetTransaction;

class AssetHistoryController extends Controller
{
    public function show($asset_id)
    {
        $asset = Asset::with('category', 'brand')->findOrFail($asset_id);
        // Order chronologically: assign → return → assign → … (oldest first)
        $history = AssetTransaction::with(['employee', 'location'])
                    ->where('asset_id', $asset_id)
                    ->orderByRaw('COALESCE(return_date, issue_date) ASC')
                    ->get();

        return view('assets.history', compact('asset', 'history'));
    }
}

