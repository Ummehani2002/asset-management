<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\AssetTransaction;

class EmployeeAssetController extends Controller
{
    // Show the form and results
    public function getAssets(Request $request)
    {
        $assets = null;

        if ($request->has('employee_id')) {
            // Log the input for debugging
            \Log::info('Searching for employee_id: ' . $request->employee_id);

            // Find employee by employee_id (e.g., EMP001)
            $employee = Employee::where('employee_id', $request->employee_id)->first();

            if (!$employee) {
                return back()->with('error', 'Employee not found.');
            }

            // Now fetch the assets using the internal ID
            $assets = Asset::with(['category', 'employee'])
                ->where('employee_id', $employee->id)
                ->get();

            \Log::info('Assets found: ' . $assets->count());
        }

        return view('employee-assets', compact('assets'));
    }
    
public function index(Request $request)
{
    $employee = null;
    if ($request->filled('employee_id')) {
        $employee = Employee::with('assets.assetCategory')->find($request->employee_id);
        if (!$employee) {
            return back()->with('error', 'Employee not found.');
        }
    }

    return view('employee-assets', compact('employee'));
}


public function showAssets($id)
{
    $employee = Employee::with('assets.assetCategory')->findOrFail($id);
    return view('employee-assets-single', compact('employee')); // updated view name
}

public function export($id, Request $request)
{
    $employee = Employee::findOrFail($id);

    // Use same logic as AssetController::getAssetsByEmployee - only currently assigned assets
    $assignedAssets = Asset::where('status', 'assigned')
        ->with(['category', 'brand', 'location', 'latestTransaction.location'])
        ->get()
        ->filter(function ($asset) use ($id) {
            $latestTxn = $asset->latestTransaction;
            return $latestTxn
                && $latestTxn->transaction_type === 'assign'
                && $latestTxn->employee_id == $id;
        });

    $assets = $assignedAssets->map(function ($asset) {
        $latestTxn = $asset->latestTransaction;
        // Get location: prefer latest assign txn, fallback to any assign txn with location, then asset's location
        $locationName = '-';
        if ($latestTxn && $latestTxn->location) {
            $locationName = $latestTxn->location->location_name;
        } else {
            $txnWithLocation = AssetTransaction::where('asset_id', $asset->id)
                ->where('transaction_type', 'assign')
                ->whereNotNull('location_id')
                ->with('location')
                ->latest()
                ->first();
            if ($txnWithLocation && $txnWithLocation->location) {
                $locationName = $txnWithLocation->location->location_name;
            } elseif ($asset->location) {
                $locationName = $asset->location->location_name;
            }
        }
        return [
            'asset_id' => $asset->asset_id ?? '-',
            'category' => $asset->category ? $asset->category->category_name : '-',
            'brand' => $asset->brand ? $asset->brand->name : '-',
            'serial_number' => $asset->serial_number ?? '-',
            'po_number' => $asset->po_number ?? '-',
            'location' => $locationName,
            'issue_date' => $latestTxn ? ($latestTxn->issue_date ?? '-') : '-',
            'status' => ucfirst($asset->status ?? 'N/A'),
        ];
    })->values();

    $format = $request->get('format', 'pdf');
    $employeeName = $employee->name ?? $employee->entity_name ?? 'Employee';

    if ($format === 'excel' || $format === 'csv') {
        return $this->exportExcel($assets, $employeeName);
    } else {
        return $this->exportPdf($assets, $employeeName);
    }
}

private function exportPdf($assets, $employeeName)
{
    $html = view('employee-assets.export-pdf', compact('assets', 'employeeName'))->render();
    return response()->streamDownload(function() use ($html) {
        echo $html;
    }, 'employee-assets-' . str_replace(' ', '-', $employeeName) . '-' . date('Y-m-d') . '.html', [
        'Content-Type' => 'text/html',
    ]);
}

private function exportExcel($assets, $employeeName)
{
    $filename = 'employee-assets-' . str_replace(' ', '-', $employeeName) . '-' . date('Y-m-d') . '.csv';
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ];

    $callback = function() use ($assets) {
        $file = fopen('php://output', 'w');
        
        // Headers
        fputcsv($file, [
            '#', 'Asset ID', 'Category', 'Brand', 'Serial Number', 
            'PO Number', 'Location Name', 'Issue Date', 'Status'
        ]);

        // Data
        foreach ($assets as $index => $asset) {
            fputcsv($file, [
                $index + 1,
                $asset['asset_id'],
                $asset['category'],
                $asset['brand'],
                $asset['serial_number'],
                $asset['po_number'],
                $asset['location'],
                $asset['issue_date'],
                $asset['status'],
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

}
