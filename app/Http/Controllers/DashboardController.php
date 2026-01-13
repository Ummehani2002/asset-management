<?php
namespace App\Http\Controllers;
use App\Models\AssetCategory;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            // Check if required tables exist
            if (!$this->checkDatabaseTables(['asset_categories'])) {
                return redirect()->route('login')->withErrors(['error' => 'Database tables not found. Please run migrations: php artisan migrate --force']);
            }

            $categoryCounts = AssetCategory::withCount('assets')->get();

            return view('dashboard', compact('categoryCounts'));
        } catch (\Exception $e) {
            return $this->handleDatabaseError($e);
        }
    }

    public function export(Request $request)
    {
        try {
            // Check if required tables exist
            if (!$this->checkDatabaseTables(['asset_categories'])) {
                return redirect()->route('dashboard')->withErrors(['error' => 'Database tables not found. Please run migrations: php artisan migrate --force']);
            }

            $categoryCounts = AssetCategory::withCount('assets')->get();
            $format = $request->get('format', 'pdf');

            if ($format === 'csv') {
                return $this->exportCsv($categoryCounts);
            } else {
                return $this->exportPdf($categoryCounts);
            }
        } catch (\Exception $e) {
            return $this->handleDatabaseError($e, 'dashboard');
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
