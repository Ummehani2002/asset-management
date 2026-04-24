<?php
namespace App\Http\Controllers;
use App\Models\AssetCategory;
use App\Models\Asset;
use App\Models\Entity;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    /**
     * Scope assets query by entity (uses entity_id directly, or falls back to location.location_entity).
     */
    private function scopeAssetsByEntity($query, $entityId, $entityName = null)
    {
        if (empty($entityId) && empty($entityName)) {
            return $query;
        }
        
        // Use entity_id directly if column exists, otherwise fall back to location-based lookup
        if (Schema::hasColumn('assets', 'entity_id') && $entityId) {
            return $query->where('entity_id', $entityId);
        }
        
        // Fallback to location-based lookup for backward compatibility
        if ($entityName) {
            $locationIds = Location::where('location_entity', $entityName)->pluck('id');
            return $query->whereIn('location_id', $locationIds);
        }
        
        return $query;
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
            $scrapAssets = 0;

            if (Schema::hasTable('assets')) {
                $assetQuery = Asset::whereIn('status', $relevantStatuses);
                $this->scopeAssetsByEntity($assetQuery, $selectedEntityId, $entityName);
                $totalAssets = $assetQuery->count();

                $availQuery = Asset::whereIn('status', ['available', 'returned']);
                $this->scopeAssetsByEntity($availQuery, $selectedEntityId, $entityName);
                $availableAssets = $availQuery->count();

                $scrapQuery = Asset::where('status', 'scrap');
                $this->scopeAssetsByEntity($scrapQuery, $selectedEntityId, $entityName);
                $scrapAssets = $scrapQuery->count();
            }

            // Check if required tables exist
            if (!Schema::hasTable('asset_categories')) {
                Log::warning('asset_categories table does not exist');
                $categoryCounts = collect([]);
            } else {
                try {
                    $categoryCounts = AssetCategory::withCount([
                        'assets as assets_count' => function ($q) use ($selectedEntityId, $entityName) {
                            $q->whereIn('status', ['assigned', 'available', 'returned']);
                            $this->scopeAssetsByEntity($q, $selectedEntityId, $entityName);
                        },
                        'assets as available_count' => function ($q) use ($selectedEntityId, $entityName) {
                            $q->whereIn('status', ['available', 'returned']);
                            $this->scopeAssetsByEntity($q, $selectedEntityId, $entityName);
                        },
                        'assets as assigned_count' => function ($q) use ($selectedEntityId, $entityName) {
                            $q->where('status', 'assigned');
                            $this->scopeAssetsByEntity($q, $selectedEntityId, $entityName);
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

            return view('dashboard', compact('categoryCounts', 'totalAssets', 'availableAssets', 'scrapAssets', 'entities', 'selectedEntityId', 'selectedEntity'));
        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            $categoryCounts = collect([]);
            $totalAssets = 0;
            $availableAssets = 0;
            $scrapAssets = 0;
            $entities = Schema::hasTable('entities') ? Entity::orderBy('name')->get() : collect([]);
            $selectedEntityId = null;
            $selectedEntity = null;
            return view('dashboard', compact('categoryCounts', 'totalAssets', 'availableAssets', 'scrapAssets', 'entities', 'selectedEntityId', 'selectedEntity'))
                ->with('warning', 'Some data could not be loaded. Please ensure migrations are run.');
        }
    }

    public function export(Request $request)
    {
        $entities = Schema::hasTable('entities') ? Entity::orderBy('name')->get() : collect([]);
        $selectedEntityId = $request->get('entity');
        $selectedEntity = null;
        $entityName = null;
        if ($selectedEntityId && $entities->isNotEmpty()) {
            $selectedEntity = $entities->firstWhere('id', $selectedEntityId);
            $entityName = $selectedEntity ? $selectedEntity->name : null;
        }

        $categoryCounts = AssetCategory::withCount([
            'assets as assets_count' => function ($q) use ($selectedEntityId, $entityName) {
                $q->whereIn('status', ['assigned', 'available', 'returned']);
                $this->scopeAssetsByEntity($q, $selectedEntityId, $entityName);
            },
            'assets as available_count' => function ($q) use ($selectedEntityId, $entityName) {
                $q->whereIn('status', ['available', 'returned']);
                $this->scopeAssetsByEntity($q, $selectedEntityId, $entityName);
            },
            'assets as assigned_count' => function ($q) use ($selectedEntityId, $entityName) {
                $q->where('status', 'assigned');
                $this->scopeAssetsByEntity($q, $selectedEntityId, $entityName);
            }
        ])->get()
            ->filter(fn ($c) => ($c->assets_count ?? 0) > 0)
            ->values();

        $format = $request->get('format', 'pdf');

        if ($format === 'csv') {
            return $this->exportCsv($categoryCounts, $selectedEntity);
        } else {
            return $this->exportPdf($categoryCounts, $selectedEntity);
        }
    }

    private function exportPdf($categoryCounts, $selectedEntity = null)
    {
        $pdf = \PDF::loadView('dashboard.export-pdf', compact('categoryCounts', 'selectedEntity'));
        $entityPart = $selectedEntity ? str_replace(' ', '-', strtolower($selectedEntity->name)) : 'all-entities';
        return $pdf->download('dashboard-report-' . $entityPart . '-' . date('Y-m-d') . '.pdf');
    }

    private function exportCsv($categoryCounts, $selectedEntity = null)
    {
        $entityPart = $selectedEntity ? str_replace(' ', '-', strtolower($selectedEntity->name)) : 'all-entities';
        $filename = 'dashboard-report-' . $entityPart . '-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($categoryCounts) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, ['#', 'Category Name', 'Total Assets', 'Available', 'Assigned']);

            // Data
            foreach ($categoryCounts as $index => $category) {
                fputcsv($file, [
                    $index + 1,
                    $category->category_name,
                    $category->assets_count,
                    $category->available_count ?? 0,
                    $category->assigned_count ?? 0,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportAssets(Request $request)
    {
        $query = Asset::with(['category', 'brand', 'entity', 'location'])->orderBy('asset_id');

        $selectedEntityId = $request->get('entity');
        $selectedEntity = null;
        if (!empty($selectedEntityId) && Schema::hasTable('entities')) {
            $selectedEntity = Entity::find($selectedEntityId);
        }

        $entityName = $selectedEntity?->name;
        $this->scopeAssetsByEntity($query, $selectedEntityId, $entityName);
        $assets = $query->get();

        $format = $request->get('format', 'pdf');
        if ($format === 'csv') {
            return $this->exportAssetsCsv($assets, $selectedEntity);
        }

        return $this->exportAssetsPdf($assets, $selectedEntity);
    }

    private function exportAssetsPdf($assets, $selectedEntity)
    {
        $pdf = \PDF::loadView('dashboard.export-assets-pdf', [
            'assets' => $assets,
            'selectedEntity' => $selectedEntity,
        ])->setPaper('a4', 'landscape');

        $entityPart = $selectedEntity ? str_replace(' ', '-', strtolower($selectedEntity->name)) : 'all-entities';
        return $pdf->download('dashboard-assets-' . $entityPart . '-' . date('Y-m-d') . '.pdf');
    }

    private function exportAssetsCsv($assets, $selectedEntity): StreamedResponse
    {
        $entityPart = $selectedEntity ? str_replace(' ', '-', strtolower($selectedEntity->name)) : 'all-entities';
        $filename = 'dashboard-assets-' . $entityPart . '-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($assets) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                '#', 'Asset ID', 'Entity', 'Category', 'Brand', 'Serial Number',
                'Status', 'Purchase Date', 'Warranty Start', 'Expiry Date',
                'PO Number', 'Vendor Name', 'Value',
            ]);

            foreach ($assets as $index => $asset) {
                fputcsv($file, [
                    $index + 1,
                    $asset->asset_id ?? 'N/A',
                    $asset->entity->name ?? $asset->location->location_entity ?? 'N/A',
                    $asset->category->category_name ?? 'N/A',
                    $asset->brand->name ?? 'N/A',
                    $asset->serial_number ?? 'N/A',
                    $asset->status ?? 'N/A',
                    $asset->purchase_date ?? 'N/A',
                    $asset->warranty_start ?? 'N/A',
                    $asset->expiry_date ?? 'N/A',
                    $asset->po_number ?? 'N/A',
                    $asset->vendor_name ?? '-',
                    $asset->value ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
