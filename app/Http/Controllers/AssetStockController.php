<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetStockReceipt;
use App\Models\ItConsumable;
use App\Models\ItConsumableIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AssetStockController extends Controller
{
    public function index()
    {
        if (! Auth::check()) {
            abort(403);
        }

        if (! Schema::hasTable('asset_categories')) {
            return view('asset_stock.index', [
                'rows' => collect(),
                'receipts' => collect(),
                'totals' => ['received' => 0, 'assigned' => 0, 'issued' => 0, 'scrap' => 0, 'in_stock' => 0],
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

        $issuedByCategory = collect();
        if (Schema::hasTable('it_consumable_issues') && Schema::hasColumn('it_consumables', 'asset_category_id')) {
            $issuedByCategory = ItConsumableIssue::query()
                ->join('it_consumables', 'it_consumables.id', '=', 'it_consumable_issues.it_consumable_id')
                ->whereNotNull('it_consumables.asset_category_id')
                ->selectRaw('it_consumables.asset_category_id, SUM(it_consumable_issues.quantity) as issued_qty')
                ->groupBy('it_consumables.asset_category_id')
                ->pluck('issued_qty', 'asset_category_id');
        }

        $rows = $categories->map(function (AssetCategory $category) use ($receivedByCategory, $statusCounts, $issuedByCategory) {
            $counts = collect($statusCounts->get($category->id, []))->pluck('total', 'status');
            $received = (int) ($receivedByCategory[$category->id] ?? 0);
            $assigned = (int) ($counts['assigned'] ?? 0);
            $maintenance = (int) ($counts['under_maintenance'] ?? 0);
            $scrap = (int) ($counts['scrap'] ?? 0);
            $issued = (int) ($issuedByCategory[$category->id] ?? 0);

            // Manual stock only: what you receive minus what you issue via IT Consumables.
            $inStock = $received - $issued;

            return [
                'category' => $category,
                'received' => $received,
                'assigned' => $assigned,
                'maintenance' => $maintenance,
                'issued' => $issued,
                'scrap' => $scrap,
                'in_stock' => $inStock,
            ];
        });

        $receipts = Schema::hasTable('asset_stock_receipts')
            ? AssetStockReceipt::with(['category', 'user'])->latest()->limit(30)->get()
            : collect();

        $totals = [
            'received' => (int) $rows->sum('received'),
            'assigned' => (int) $rows->sum('assigned'),
            'issued' => (int) $rows->sum('issued'),
            'scrap' => (int) $rows->sum('scrap'),
            'in_stock' => (int) $rows->sum('in_stock'),
        ];

        return view('asset_stock.index', compact('rows', 'receipts', 'totals', 'categories'));
    }

    public function store(Request $request)
    {
        if (! Auth::check()) {
            abort(403);
        }

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

    /**
     * Available stock for a category after asset assign/scrap and consumable issues.
     */
    public static function availableQty(int $categoryId): int
    {
        $received = Schema::hasTable('asset_stock_receipts')
            ? (int) AssetStockReceipt::where('asset_category_id', $categoryId)->sum('quantity')
            : 0;

        $issued = 0;
        if (Schema::hasTable('it_consumable_issues') && Schema::hasColumn('it_consumables', 'asset_category_id')) {
            $issued = (int) ItConsumableIssue::query()
                ->whereHas('consumable', fn ($q) => $q->where('asset_category_id', $categoryId))
                ->sum('quantity');
        }

        return $received - $issued;
    }
}
