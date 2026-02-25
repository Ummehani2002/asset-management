<?php
namespace App\Http\Controllers;
use App\Models\AssetCategory;
use App\Models\Asset;
use App\Models\Entity;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Scope assets query by entity (via location.location_entity = entity name).
     */
    private function scopeAssetsByEntity($query, $entityName)
    {
        if (empty($entityName)) {
            return $query;
        }
        $locationIds = Location::where('location_entity', $entityName)->pluck('id');
        return $query->whereIn('location_id', $locationIds);
    }

    public function index(Request $request)
    {
        try {
            $entities = collect([]);
            if (Schema::hasTable('entities')) {
                $entities = Entity::orderBy('name')->get();
            }

            $selectedEntityId = $request->get('entity');
            $selectedEntity = null;
            $entityName = null;
            if ($selectedEntityId && $entities->isNotEmpty()) {
                $selectedEntity = $entities->firstWhere('id', $selectedEntityId);
                $entityName = $selectedEntity ? $selectedEntity->name : null;
            }

            // Only count assets that are assigned, available, or returned (exclude under_maintenance etc.)
            $relevantStatuses = ['assigned', 'available', 'returned'];
            $totalAssets = 0;
            $availableAssets = 0;

            if (Schema::hasTable('assets')) {
                $assetQuery = Asset::whereIn('status', $relevantStatuses);
                $this->scopeAssetsByEntity($assetQuery, $entityName);
                $totalAssets = $assetQuery->count();

                $availQuery = Asset::whereIn('status', ['available', 'returned']);
                $this->scopeAssetsByEntity($availQuery, $entityName);
                $availableAssets = $availQuery->count();
            }

            // Check if required tables exist
            if (!Schema::hasTable('asset_categories')) {
                Log::warning('asset_categories table does not exist');
                $categoryCounts = collect([]);
            } else {
                try {
                    $categoryCounts = AssetCategory::withCount([
                        'assets as assets_count' => function ($q) use ($entityName) {
                            $q->whereIn('status', ['assigned', 'available', 'returned']);
                            $this->scopeAssetsByEntity($q, $entityName);
                        },
                        'assets as available_count' => function ($q) use ($entityName) {
                            $q->whereIn('status', ['available', 'returned']);
                            $this->scopeAssetsByEntity($q, $entityName);
                        },
                        'assets as assigned_count' => function ($q) use ($entityName) {
                            $q->where('status', 'assigned');
                            $this->scopeAssetsByEntity($q, $entityName);
                        }
                    ])->get()
                    ->filter(fn ($c) => ($c->assets_count ?? 0) > 0)
                    ->values();
                } catch (\Exception $e) {
                    Log::warning('Error loading asset categories: ' . $e->getMessage());
                    $categoryCounts = AssetCategory::all()->map(function ($category) {
                        $category->assets_count = 0;
                        $category->available_count = 0;
                        $category->assigned_count = 0;
                        return $category;
                    });
                }
            }

            return view('dashboard', compact('categoryCounts', 'totalAssets', 'availableAssets', 'entities', 'selectedEntityId', 'selectedEntity'));
        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            $categoryCounts = collect([]);
            $totalAssets = 0;
            $availableAssets = 0;
            $entities = Schema::hasTable('entities') ? Entity::orderBy('name')->get() : collect([]);
            $selectedEntityId = null;
            $selectedEntity = null;
            return view('dashboard', compact('categoryCounts', 'totalAssets', 'availableAssets', 'entities', 'selectedEntityId', 'selectedEntity'))
                ->with('warning', 'Some data could not be loaded. Please ensure migrations are run.');
        }
    }

    public function export(Request $request)
    {
        $categoryCounts = AssetCategory::withCount([
            'assets',
            'assets as available_count' => function ($q) {
                $q->whereIn('status', ['available', 'returned']);
            }
        ])->get();
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
            fputcsv($file, ['#', 'Category Name', 'Total Assets', 'Available']);

            // Data
            foreach ($categoryCounts as $index => $category) {
                fputcsv($file, [
                    $index + 1,
                    $category->category_name,
                    $category->assets_count,
                    $category->available_count ?? 0,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
