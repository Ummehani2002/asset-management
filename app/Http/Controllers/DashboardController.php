<?php
namespace App\Http\Controllers;
use App\Models\AssetCategory;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $categoryCounts = AssetCategory::withCount('assets')->get();

        return view('dashboard', compact('categoryCounts'));
    }

    public function export(Request $request)
    {
        $categoryCounts = AssetCategory::withCount('assets')->get();
        $format = $request->get('format', 'pdf');

        if ($format === 'csv') {
            return $this->exportCsv($categoryCounts);
        } else {
            return $this->exportPdf($categoryCounts);
        }
    }

    private function exportPdf($categoryCounts)
    {
        $pdf = \PDF::loadView('dashboard.export-pdf', compact('categoryCounts'));
        return $pdf->download('dashboard-report-' . date('Y-m-d') . '.pdf');
    }

    private function exportCsv($categoryCounts)
    {
        $filename = 'dashboard-report-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($categoryCounts) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, ['#', 'Category Name', 'Total Assets']);

            // Data
            foreach ($categoryCounts as $index => $category) {
                fputcsv($file, [
                    $index + 1,
                    $category->category_name,
                    $category->assets_count,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
