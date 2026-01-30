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
        // All transactions in chronological order (oldest first): assign, return, maintenance
        $history = AssetTransaction::with(['employee', 'location'])
                    ->where('asset_id', $asset_id)
                    ->orderBy('created_at', 'asc')
                    ->get();

        return view('assets.history', compact('asset', 'history'));
    }
}

