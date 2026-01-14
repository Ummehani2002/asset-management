<?php
namespace App\Http\Controllers;
use App\Models\AssetCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            // Check if required tables exist
            if (!Schema::hasTable('asset_categories')) {
                Log::warning('asset_categories table does not exist');
                $categoryCounts = collect([]); // Empty collection
            } else {
                // Try to get category counts, but handle if assets table doesn't exist
                try {
        $categoryCounts = AssetCategory::withCount('assets')->get();
                } catch (\Exception $e) {
                    Log::warning('Error loading asset categories: ' . $e->getMessage());
                    // Fallback: get categories without count
                    $categoryCounts = AssetCategory::all()->map(function ($category) {
                        $category->assets_count = 0;
                        return $category;
                    });
                }
            }

        return view('dashboard', compact('categoryCounts'));
        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Return empty dashboard instead of crashing
            $categoryCounts = collect([]);
            return view('dashboard', compact('categoryCounts'))
                ->with('warning', 'Some data could not be loaded. Please ensure migrations are run.');
        }
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
