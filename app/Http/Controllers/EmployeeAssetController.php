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
    $employee = Employee::with(['assetTransactions.asset.category', 'assetTransactions.asset.brand', 'assetTransactions.location'])
        ->findOrFail($id);

    $assets = $employee->assetTransactions->map(function ($txn) {
        return [
            'asset_id' => $txn->asset->asset_id ?? '-',
            'category' => $txn->asset->category->category_name ?? '-',
            'brand' => $txn->asset->brand->name ?? '-',
            'serial_number' => $txn->asset->serial_number ?? '-',
            'po_number' => $txn->asset->po_number ?? '-',
            'location' => $txn->location->location_name ?? '-',
            'issue_date' => $txn->issue_date ?? '-',
            'status' => ucfirst($txn->transaction_type ?? 'N/A'),
        ];
    });

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
            'PO Number', 'Location', 'Issue Date', 'Status'
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
