<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SimcardTransaction;
use App\Models\InternetService; 
use App\Models\Asset;
use App\Models\AssetCategory; 

class ReportController extends Controller
{
    // SIM Card Report
    public function simcard()
    {
        $transactions = SimcardTransaction::orderBy('created_at', 'desc')->paginate(20);

        return view('reports.simcard', compact('transactions'));
    }
    public function internet()
    {
        $internetServices = InternetService::with(['project', 'personInCharge'])->latest()->paginate(20);

        return view('reports.internet', compact('internetServices'));
    }
    public function assetSummary()
    {
        // Group assets by entity (from project or category)
        $assets = Asset::with(['category', 'brand'])
                       ->get()
                       ->groupBy('entity');  // assuming 'entity' field exists in Asset table

        return view('reports.asset_summary', compact('assets'));
    }
    // You can add other reports here
}
