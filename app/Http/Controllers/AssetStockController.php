<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetStockReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AssetStockController extends Controller
{
    public function index()
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        if (! Schema::hasTable('asset_categories')) {
            return view('asset_stock.index', [
                'rows' => collect(),
                'receipts' => collect(),
                'totals' => ['received' => 0, 'assigned' => 0, 'scrap' => 0, 'in_stock' => 0],
            ])->with('warning', 'Asset categories are not set up yet.');
        }

        $categories = AssetCategory::orderBy('category_name')->get();
        $receivedByCategory = Schema::hasTable('asset_stock_receipts')
            ? AssetStockReceipt::query()
                ->selectRaw('asset_category_id, SUM(quantity) as received_qty')
                ->groupBy('asset_category_id')
                ->pluck('received_qty', 'asset_category_id')
            : collect();

        $statusCounts = Schema::hasTable('assets')
            ? Asset::query()
                ->selectRaw('asset_category_id, status, COUNT(*) as total')
                ->groupBy('asset_category_id', 'status')
                ->get()
                ->groupBy('asset_category_id')
            : collect();

        $rows = $categories->map(function (AssetCategory $category) use ($receivedByCategory, $statusCounts) {
            $counts = collect($statusCounts->get($category->id, []))->pluck('total', 'status');
            $received = (int) ($receivedByCategory[$category->id] ?? 0);
            $assigned = (int) ($counts['assigned'] ?? 0);
            $maintenance = (int) ($counts['under_maintenance'] ?? 0);
            $scrap = (int) ($counts['scrap'] ?? 0);
            $out = $assigned + $maintenance + $scrap;

            return [
                'category' => $category,
                'received' => $received,
                'assigned' => $assigned,
                'maintenance' => $maintenance,
                'scrap' => $scrap,
                'in_stock' => $received - $out,
            ];
        });

        $receipts = Schema::hasTable('asset_stock_receipts')
            ? AssetStockReceipt::with(['category', 'user'])->latest()->limit(30)->get()
            : collect();

        $totals = [
            'received' => (int) $rows->sum('received'),
            'assigned' => (int) $rows->sum('assigned'),
            'scrap' => (int) $rows->sum('scrap'),
            'in_stock' => (int) $rows->sum('in_stock'),
        ];

        return view('asset_stock.index', compact('rows', 'receipts', 'totals', 'categories'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $data = $request->validate([
            'asset_category_id' => 'required|exists:asset_categories,id',
            'quantity' => 'required|integer|min:1',
            'received_date' => 'required|date',
            'remarks' => 'nullable|string|max:255',
        ]);

        AssetStockReceipt::create([
            'asset_category_id' => $data['asset_category_id'],
            'quantity' => $data['quantity'],
            'received_date' => $data['received_date'],
            'remarks' => $data['remarks'] ?? null,
            'user_id' => Auth::id(),
        ]);

        return redirect()
            ->route('asset-stock.index')
            ->with('success', 'Received quantity added to stock.');
    }
}
