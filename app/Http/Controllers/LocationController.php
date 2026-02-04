<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Location;
use App\Imports\LocationsImport;
use App\Models\Asset;
use App\Models\AssetTransaction;
use App\Helpers\EntityHelper;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class LocationController extends Controller
{
    public function index()
    {
        try {
            // Test database connection first
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                Log::error('Database connection failed: ' . $e->getMessage());
                Log::error('Connection error details: ' . $e->getFile() . ':' . $e->getLine());
                $entities = EntityHelper::getEntities();
                return view('location.index', compact('entities'))
                    ->with('error', 'Database connection failed. Please check your database credentials in Laravel Cloud environment variables.');
            }

            if (!Schema::hasTable('locations')) {
                Log::warning('locations table does not exist');
                $entities = EntityHelper::getEntities();
                return view('location.index', compact('entities'))
                    ->with('warning', 'Database tables not found. Please run migrations: php artisan migrate --force');
            }

            $entities = EntityHelper::getEntities();
            return view('location.index', compact('entities'));
        } catch (\Throwable $e) {
            Log::error('Location index fatal error: ' . $e->getMessage());
            Log::error('Error class: ' . get_class($e));
            Log::error('Stack trace: ' . $e->getTraceAsString());
            Log::error('File: ' . $e->getFile() . ':' . $e->getLine());
            
            $entities = EntityHelper::getEntities();
            return view('location.index', compact('entities'))
                ->with('error', 'An error occurred. Please check Laravel Cloud logs for details.');
        }
    }

    public function search(Request $request)
    {
        $locations = collect([]);
        $entities = EntityHelper::getEntities();

        try {
            if (!Schema::hasTable('locations')) {
                return view('location.search', compact('locations', 'entities'))
                    ->with('warning', 'Database tables not found. Please run migrations: php artisan migrate --force');
            }

            $query = Location::query();

            if ($request->filled('entity')) {
                $query->where('location_entity', $request->entity);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('location_id', 'like', "%{$search}%")
                      ->orWhere('location_name', 'like', "%{$search}%")
                      ->orWhere('location_country', 'like', "%{$search}%");
                });
            }

            $locations = $query->orderBy('location_id')->get();
            if (!$locations instanceof \Illuminate\Support\Collection) {
                $locations = collect($locations);
            }
        } catch (\Exception $e) {
            Log::error('Location search error: ' . $e->getMessage());
        }

        return view('location.search', compact('locations', 'entities'));
    }

    public function store(Request $request)
{
    try {
        if (!Schema::hasTable('locations')) {
            Log::error('locations table does not exist');
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Database table not found. Please run migrations: php artisan migrate --force']);
        }

        $request->validate([
            'location_id' => 'required|unique:locations,location_id',
            'location_name' => 'required|string',
            'location_country' => 'nullable|string',
            'location_entity' => 'required|string',
        ]);

        $locationData = [
            'location_id' => $request->location_id,
            'location_name' => $request->location_name,
            'location_country' => $request->location_country,
            'location_entity' => $request->location_entity,
        ];
        
        Log::info('Creating location with data:', $locationData);
        
        $location = Location::create($locationData);
        
        Log::info('Location created successfully. ID: ' . $location->id);
        
        // Verify the location was actually saved
        $savedLocation = Location::find($location->id);
        if (!$savedLocation) {
            Log::error('Location was not saved to database!');
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to save location. Please try again.']);
        }

        return redirect()->route('location-master.index')->with('success', 'Location added successfully.');
    } catch (\Illuminate\Validation\ValidationException $e) {
        throw $e;
    } catch (\Illuminate\Database\QueryException $e) {
        Log::error('Location store database error: ' . $e->getMessage());
        return redirect()
            ->back()
            ->withInput()
            ->withErrors(['error' => 'Database error occurred. Please ensure migrations are run: php artisan migrate --force']);
    } catch (\Exception $e) {
        Log::error('Location store error: ' . $e->getMessage());
        return redirect()
            ->back()
            ->withInput()
            ->withErrors(['error' => 'An error occurred while saving the location. Please try again.']);
    }
}
public function edit($id)
{
    $location = Location::findOrFail($id);
    return view('location.edit', compact('location'));
}
public function update(Request $request, $id)
{
    $request->validate([
        'location_name'     => 'required|string|max:255',
        'location_country' => 'nullable|string|max:255',
        'location_entity'   => 'required|string|max:255',
    ]);

    $location = Location::findOrFail($id);
    $location->location_name = $request->input('location_name');
    $location->location_country = $request->input('location_country');
    $location->location_entity = $request->input('location_entity');
    $location->save();

    return redirect()->route('location-master.index')->with('success', 'Location updated successfully.');
}

public function destroy($id)
{
   
    DB::table('asset_transactions')->where('location_id', $id)->delete();

    Location::destroy($id);
    return redirect()->route('location-master.index')->with('success', 'Location and related asset transactions deleted successfully.');
}

public function showImportForm()
{
    return view('location.import');
}

public function import(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:xlsx,xls,csv'
    ]);
    Excel::import(new \App\Imports\LocationsImport, $request->file('file'));
    return back()->with('success', 'Locations imported successfully!');
}

public function autocomplete(Request $request)
{
    $query = trim($request->get('query', ''));
    
    if(empty($query)) {
        return response()->json([]);
    }

    // Search by location_name (starts with first, then contains) or location_id
    $locations = Location::where(function($q) use ($query) {
            $q->where('location_name', 'LIKE', "{$query}%")  // Starts with (priority)
              ->orWhere('location_name', 'LIKE', "%{$query}%") // Contains
              ->orWhere('location_id', 'LIKE', "{$query}%"); // Location ID starts with
        })
        ->orderBy('location_name', 'asc')
        ->take(15)
        ->get(['id', 'location_id', 'location_name', 'location_country']);

    // Sort results: names starting with query first
    $locations = $locations->sortBy(function($location) use ($query) {
        $name = strtolower($location->location_name ?? '');
        $queryLower = strtolower($query);
        
        if(strpos($name, $queryLower) === 0) return 1; // Starts with
        return 2; // Contains
    })->values();

    return response()->json($locations);
}


    // GET /locations/{id}/assets
    public function assets($id)
    {
        // Get asset IDs from latest transactions where location_id matches and type is 'assign'
        $assetIds = AssetTransaction::where('location_id', $id)
            ->where('transaction_type', 'assign')
            ->whereNotNull('asset_id')
            ->select('asset_id')
            ->selectRaw('MAX(id) as max_id')
            ->groupBy('asset_id')
            ->get()
            ->pluck('asset_id');

        if ($assetIds->isEmpty()) {
            return response()->json([]);
        }

        // Get assets with their relationships
        $assets = Asset::whereIn('id', $assetIds)
            ->with(['assetCategory', 'brand'])
            ->orderBy('asset_id')
            ->get()
            ->map(function($asset) {
                return [
                    'asset_id' => $asset->asset_id ?? 'N/A',
                    'category' => $asset->assetCategory->category_name ?? 'N/A',
                    'brand' => $asset->brand->name ?? 'N/A',
                    'serial_number' => $asset->serial_number ?? 'N/A',
                    'po_number' => $asset->po_number ?? 'N/A',
                    'purchase_date' => $asset->purchase_date ? \Carbon\Carbon::parse($asset->purchase_date)->format('Y-m-d') : 'N/A',
                    'expiry_date' => $asset->expiry_date ? \Carbon\Carbon::parse($asset->expiry_date)->format('Y-m-d') : 'N/A',
                    'status' => $asset->status ?? 'N/A'
                ];
            });

        return response()->json($assets);
    }

    public function exportAssets($id, Request $request)
    {
        // Get asset IDs from latest transactions where location_id matches and type is 'assign'
        $assetIds = AssetTransaction::where('location_id', $id)
            ->where('transaction_type', 'assign')
            ->whereNotNull('asset_id')
            ->select('asset_id')
            ->selectRaw('MAX(id) as max_id')
            ->groupBy('asset_id')
            ->get()
            ->pluck('asset_id');

        if ($assetIds->isEmpty()) {
            return back()->with('error', 'No assets found for this location.');
        }

        // Get assets with their relationships
        $assets = Asset::whereIn('id', $assetIds)
            ->with(['assetCategory', 'brand'])
            ->orderBy('asset_id')
            ->get()
            ->map(function($asset) {
                return [
                    'asset_id' => $asset->asset_id ?? 'N/A',
                    'category' => $asset->assetCategory->category_name ?? 'N/A',
                    'brand' => $asset->brand->name ?? 'N/A',
                    'serial_number' => $asset->serial_number ?? 'N/A',
                    'po_number' => $asset->po_number ?? 'N/A',
                    'purchase_date' => $asset->purchase_date ? \Carbon\Carbon::parse($asset->purchase_date)->format('Y-m-d') : 'N/A',
                    'expiry_date' => $asset->expiry_date ? \Carbon\Carbon::parse($asset->expiry_date)->format('Y-m-d') : 'N/A',
                    'status' => $asset->status ?? 'N/A'
                ];
            });

        $location = Location::findOrFail($id);
        $locationName = $location->location_name ?? 'Location';

        $format = $request->get('format', 'pdf');

        if ($format === 'excel' || $format === 'csv') {
            return $this->exportLocationAssetsExcel($assets, $locationName);
        } else {
            return $this->exportLocationAssetsPdf($assets, $locationName);
        }
    }

    private function exportLocationAssetsPdf($assets, $locationName)
    {
        $pdf = \PDF::loadView('location.export-assets-pdf', compact('assets', 'locationName'));
        return $pdf->download('location-assets-' . str_replace(' ', '-', $locationName) . '-' . date('Y-m-d') . '.pdf');
    }

    private function exportLocationAssetsExcel($assets, $locationName)
    {
        $filename = 'location-assets-' . str_replace(' ', '-', $locationName) . '-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($assets) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                '#', 'Asset ID', 'Category', 'Brand', 'Serial Number', 
                'PO Number', 'Purchase Date', 'Expiry Date', 'Status'
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
                    $asset['purchase_date'],
                    $asset['expiry_date'],
                    $asset['status'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function export(Request $request)
    {
        $query = Location::query();
        if ($request->filled('entity')) {
            $query->where('location_entity', $request->entity);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('location_id', 'like', "%{$search}%")
                  ->orWhere('location_name', 'like', "%{$search}%")
                  ->orWhere('location_country', 'like', "%{$search}%");
            });
        }
        $locations = $query->orderBy('location_id')->get();
        $format = $request->get('format', 'pdf');

        if ($format === 'excel' || $format === 'csv') {
            return $this->exportExcel($locations);
        } else {
            return $this->exportPdf($locations);
        }
    }

    private function exportPdf($locations)
    {
        $pdf = \PDF::loadView('location.export-pdf', compact('locations'));
        return $pdf->download('locations-report-' . date('Y-m-d') . '.pdf');
    }

    private function exportExcel($locations)
    {
        $filename = 'locations-report-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($locations) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                '#', 'Location ID', 'Country', 'Location Name', 'Entity'
            ]);

            // Data
            foreach ($locations as $index => $location) {
                fputcsv($file, [
                    $index + 1,
                    $location->location_id ?? 'N/A',
                    $location->location_country ?? 'N/A',
                    $location->location_name ?? 'N/A',
                    $location->location_entity ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

