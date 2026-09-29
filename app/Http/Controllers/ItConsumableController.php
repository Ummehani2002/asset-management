<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use App\Models\Employee;
use App\Models\ItConsumable;
use App\Models\ItConsumableIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ItConsumableController extends Controller
{
    public function index()
    {
        $issuesTableExists = Schema::hasTable('it_consumable_issues');
        $hasAllocatedQty = Schema::hasColumn('it_consumables', 'allocated_qty');
        $hasTktRefNo = Schema::hasColumn('it_consumables', 'tkt_ref_no');
        $hasCategory = Schema::hasColumn('it_consumables', 'asset_category_id');
        $search = trim((string) request('search', ''));

        $query = ItConsumable::query();
        if ($hasCategory) {
            $query->with('category');
        }
        if ($issuesTableExists) {
            $query->withSum('issues as issued_qty', 'quantity');
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search, $hasTktRefNo) {
                $q->where('id_no', 'like', '%' . $search . '%');
                if ($hasTktRefNo) {
                    $q->orWhere('tkt_ref_no', 'like', '%' . $search . '%');
                }
                $q->orWhere('item_description', 'like', '%' . $search . '%');
            });
        }

        $items = $query->latest()->get();
        if (! $issuesTableExists) {
            $items->each(function ($item) use ($hasAllocatedQty) {
                $item->issued_qty = 0;
                if (! $hasAllocatedQty) {
                    $item->allocated_qty = 1;
                }
            });
        }

        $categories = Schema::hasTable('asset_categories')
            ? AssetCategory::orderBy('category_name')->get(['id', 'category_name'])
            : collect();

        $noSerialNames = collect(config('asset_categories.no_serial_categories', []))
            ->map(fn ($n) => strtolower(trim($n)))
            ->all();

        $consumableCategories = $categories->filter(function ($cat) use ($noSerialNames) {
            return in_array(strtolower(trim($cat->category_name)), $noSerialNames, true);
        })->values();

        if ($consumableCategories->isEmpty()) {
            $consumableCategories = $categories;
        }

        return view('it-consumables.index', compact('items', 'search', 'consumableCategories'));
    }

    public function store(Request $request)
    {
        $rules = [
            'id_no' => 'required|string|max:100|unique:it_consumables,id_no',
            'item_description' => 'required|string|max:500',
            'issued_date' => 'required|date',
            'remarks' => 'nullable|string|max:1000',
        ];
        if (Schema::hasColumn('it_consumables', 'asset_category_id')) {
            $rules['asset_category_id'] = 'required|exists:asset_categories,id';
        }

        $validated = $request->validate($rules);
        $validated['tkt_ref_no'] = Schema::hasColumn('it_consumables', 'tkt_ref_no')
            ? (string) $request->input('tkt_ref_no', '')
            : null;
        if (Schema::hasColumn('it_consumables', 'tkt_ref_no') && trim((string) $validated['tkt_ref_no']) === '') {
            return redirect()->back()->withInput()->withErrors(['tkt_ref_no' => 'TKT Ref No is required.']);
        }
        $validated['allocated_qty'] = Schema::hasColumn('it_consumables', 'allocated_qty')
            ? (int) $request->input('allocated_qty', 1)
            : 1;

        ItConsumable::create($validated);

        return redirect()
            ->route('it-consumables.index')
            ->with('success', 'IT Consumable created successfully.');
    }

    public function edit($id)
    {
        $item = ItConsumable::findOrFail($id);
        $issuedQty = Schema::hasTable('it_consumable_issues')
            ? (int) $item->issues()->sum('quantity')
            : 0;
        if (! isset($item->allocated_qty)) {
            $item->allocated_qty = 1;
        }

        $categories = Schema::hasTable('asset_categories')
            ? AssetCategory::orderBy('category_name')->get(['id', 'category_name'])
            : collect();

        return view('it-consumables.edit', compact('item', 'issuedQty', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $item = ItConsumable::findOrFail($id);
        $hasAllocatedQty = Schema::hasColumn('it_consumables', 'allocated_qty');
        $issuedQty = Schema::hasTable('it_consumable_issues')
            ? (int) $item->issues()->sum('quantity')
            : 0;

        $rules = [
            'id_no' => 'required|string|max:100|unique:it_consumables,id_no,' . $item->id,
            'item_description' => 'required|string|max:500',
            'issued_date' => 'required|date',
            'remarks' => 'nullable|string|max:1000',
        ];
        if (Schema::hasColumn('it_consumables', 'asset_category_id')) {
            $rules['asset_category_id'] = 'required|exists:asset_categories,id';
        }

        $validated = $request->validate($rules);
        $validated['tkt_ref_no'] = Schema::hasColumn('it_consumables', 'tkt_ref_no')
            ? (string) $request->input('tkt_ref_no', '')
            : null;
        if (Schema::hasColumn('it_consumables', 'tkt_ref_no') && trim((string) $validated['tkt_ref_no']) === '') {
            return redirect()->back()->withInput()->withErrors(['tkt_ref_no' => 'TKT Ref No is required.']);
        }
        $validated['allocated_qty'] = $hasAllocatedQty
            ? (int) $request->input('allocated_qty', max(1, $issuedQty))
            : 1;
        if ($validated['allocated_qty'] < max(1, $issuedQty)) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['allocated_qty' => 'Allocated quantity cannot be less than already issued quantity.']);
        }

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

    public function issueForm($id)
    {
        if (! Schema::hasTable('it_consumable_issues') || ! Schema::hasColumn('it_consumables', 'allocated_qty')) {
            return redirect()
                ->route('it-consumables.index')
                ->withErrors(['error' => 'Please run migrations first to use the consumable issue form.']);
        }

        $item = ItConsumable::with(['category', 'issues' => function ($q) {
            $q->with('employee')->latest();
        }])->findOrFail($id);
        $issuedQty = (int) $item->issues()->sum('quantity');
        $remainingQty = max(0, (int) $item->allocated_qty - $issuedQty);
        $stockAvailable = $item->asset_category_id
            ? AssetStockController::availableQty((int) $item->asset_category_id)
            : null;

        return view('it-consumables.issue', compact('item', 'issuedQty', 'remainingQty', 'stockAvailable'));
    }

    public function issueStore(Request $request, $id)
    {
        if (! Schema::hasTable('it_consumable_issues') || ! Schema::hasColumn('it_consumables', 'allocated_qty')) {
            return redirect()
                ->route('it-consumables.index')
                ->withErrors(['error' => 'Please run migrations first to issue consumables.']);
        }

        $item = ItConsumable::findOrFail($id);
        $issuedQty = (int) $item->issues()->sum('quantity');
        $remainingQty = max(0, (int) $item->allocated_qty - $issuedQty);

        if ($remainingQty <= 0) {
            return redirect()
                ->route('it-consumables.issue-form', $item->id)
                ->withErrors(['quantity' => 'No remaining quantity available to issue.']);
        }

        $rules = [
            'employee_id' => 'required|exists:employees,id',
            'quantity' => 'required|integer|min:1|max:' . $remainingQty,
            'issue_date' => 'required|date',
            'remarks' => 'nullable|string|max:1000',
        ];

        $validated = $request->validate($rules);

        if ($item->asset_category_id) {
            $stockAvailable = AssetStockController::availableQty((int) $item->asset_category_id);
            if ($validated['quantity'] > $stockAvailable) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors([
                        'quantity' => "Only {$stockAvailable} left in Asset Stock for this item type. Receive more stock first.",
                    ]);
            }
        }

        $employee = Employee::find($validated['employee_id']);
        $validated['issue_to_name'] = $employee
            ? trim(($employee->name ?: $employee->entity_name ?: 'Employee') . ($employee->employee_id ? ' ('.$employee->employee_id.')' : ''))
            : 'Employee';
        $validated['it_consumable_id'] = $item->id;

        ItConsumableIssue::create($validated);

        return redirect()
            ->route('it-consumables.issue-form', $item->id)
            ->with('success', 'Consumable issued to employee. Asset Stock reduced.');
    }
}
