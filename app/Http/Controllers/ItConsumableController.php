<?php

namespace App\Http\Controllers;

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
        $search = trim((string) request('search', ''));

        if ($issuesTableExists) {
            $query = ItConsumable::withSum('issues as issued_qty', 'quantity');
            if ($search !== '') {
                $query->where(function ($q) use ($search, $hasTktRefNo) {
                    $q->where('id_no', 'like', '%' . $search . '%');
                    if ($hasTktRefNo) {
                        $q->orWhere('tkt_ref_no', 'like', '%' . $search . '%');
                    }
                });
            }
            $items = $query->latest()->get();
        } else {
            $query = ItConsumable::query();
            if ($search !== '') {
                $query->where(function ($q) use ($search, $hasTktRefNo) {
                    $q->where('id_no', 'like', '%' . $search . '%');
                    if ($hasTktRefNo) {
                        $q->orWhere('tkt_ref_no', 'like', '%' . $search . '%');
                    }
                });
            }
            $items = $query->latest()->get();
            $items->each(function ($item) use ($hasAllocatedQty) {
                $item->issued_qty = 0;
                if (!$hasAllocatedQty) {
                    $item->allocated_qty = 1;
                }
            });
        }

        return view('it-consumables.index', compact('items', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_no' => 'required|string|max:100|unique:it_consumables,id_no',
            'item_description' => 'required|string|max:500',
            'issued_date' => 'required|date',
            'remarks' => 'nullable|string|max:1000',
        ]);
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
        if (!isset($item->allocated_qty)) {
            $item->allocated_qty = 1;
        }
        return view('it-consumables.edit', compact('item', 'issuedQty'));
    }

    public function update(Request $request, $id)
    {
        $item = ItConsumable::findOrFail($id);
        $hasAllocatedQty = Schema::hasColumn('it_consumables', 'allocated_qty');
        $issuedQty = Schema::hasTable('it_consumable_issues')
            ? (int) $item->issues()->sum('quantity')
            : 0;

        $validated = $request->validate([
            'id_no' => 'required|string|max:100|unique:it_consumables,id_no,' . $item->id,
            'item_description' => 'required|string|max:500',
            'issued_date' => 'required|date',
            'remarks' => 'nullable|string|max:1000',
        ]);
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
        if (!Schema::hasTable('it_consumable_issues') || !Schema::hasColumn('it_consumables', 'allocated_qty')) {
            return redirect()
                ->route('it-consumables.index')
                ->withErrors(['error' => 'Please run migrations first to use the consumable issue form.']);
        }

        $item = ItConsumable::with(['issues' => function ($q) {
            $q->latest();
        }])->findOrFail($id);
        $issuedQty = (int) $item->issues()->sum('quantity');
        $remainingQty = max(0, (int) $item->allocated_qty - $issuedQty);

        return view('it-consumables.issue', compact('item', 'issuedQty', 'remainingQty'));
    }

    public function issueStore(Request $request, $id)
    {
        if (!Schema::hasTable('it_consumable_issues') || !Schema::hasColumn('it_consumables', 'allocated_qty')) {
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

        $validated = $request->validate([
            'issue_to_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1|max:' . $remainingQty,
            'issue_date' => 'required|date',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $validated['it_consumable_id'] = $item->id;
        ItConsumableIssue::create($validated);

        return redirect()
            ->route('it-consumables.issue-form', $item->id)
            ->with('success', 'Consumable issued successfully.');
    }
}
