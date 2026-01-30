<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Asset;
use App\Models\AssetTransaction;
use App\Models\AssetCategory;
use App\Models\CategoryFeature; // for features
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class AssetController extends Controller
{
    public function index()
    {
        try {
            // Check if required tables exist
            $hasAssetCategories = Schema::hasTable('asset_categories');
            $hasAssets = Schema::hasTable('assets');
            
            if (!$hasAssetCategories && !$hasAssets) {
                Log::warning('asset_categories and assets tables do not exist');
                $categories = collect([]);
                $assets = collect([]);
                return view('assets.index', compact('assets', 'categories'))
                    ->with('warning', 'Database tables not found. Please run migrations: php artisan migrate --force');
            }

            // Try to get categories, fallback to empty collection if table doesn't exist
            try {
                $categories = $hasAssetCategories ? AssetCategory::all() : collect([]);
            } catch (\Exception $e) {
                Log::warning('Error loading categories: ' . $e->getMessage());
                $categories = collect([]);
            }

            // Try to get assets, fallback to empty collection if table doesn't exist
            try {
                $assets = $hasAssets 
                    ? Asset::with(['category', 'brand', 'featureValues.feature'])->get() 
                    : collect([]);
            } catch (\Exception $e) {
                Log::warning('Error loading assets: ' . $e->getMessage());
                $assets = collect([]);
            }

            return view('assets.index', compact('assets', 'categories'));
        } catch (\Exception $e) {
            Log::error('Asset index error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Return empty collections instead of crashing
            $categories = collect([]);
            $assets = collect([]);
            return view('assets.index', compact('assets', 'categories'))
                ->with('warning', 'Unable to load assets. Please ensure migrations are run: php artisan migrate --force');
        }
    }
public function filter()
{
    try {
        // Check if required tables exist
        $hasAssetCategories = Schema::hasTable('asset_categories');
        
        // Get categories
        $categories = collect([]);
        if ($hasAssetCategories) {
            try {
                $categories = AssetCategory::all();
            } catch (\Exception $e) {
                Log::warning('Error loading categories: ' . $e->getMessage());
            }
        }

        return view('assets.filter', compact('categories'))
            ->with('warning', $hasAssetCategories ? null : 'Database tables not found. Please run migrations: php artisan migrate --force');
    } catch (\Exception $e) {
        Log::error('Asset filter error: ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());
        
        $categories = collect([]);
        return view('assets.filter', compact('categories'))
            ->with('warning', 'Unable to load filter data. Please ensure migrations are run: php artisan migrate --force');
    }
}

public function create()
{
    try {
        // Check if required tables exist
        $hasAssets = Schema::hasTable('assets');
        $hasAssetCategories = Schema::hasTable('asset_categories');
        
        // Default asset ID (will be generated when category is selected)
        $autoAssetId = '';
        
        // Get categories
        $categories = collect([]);
        if ($hasAssetCategories) {
            try {
                $categories = \App\Models\AssetCategory::all();
            } catch (\Exception $e) {
                Log::warning('Error loading categories: ' . $e->getMessage());
            }
        }

        return view('assets.create', compact('autoAssetId', 'categories'))
            ->with('warning', $hasAssetCategories ? null : 'Database tables not found. Please run migrations: php artisan migrate --force');
    } catch (\Exception $e) {
        Log::error('Asset create error: ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());
        
        // Return with default values
        $autoAssetId = '';
        $categories = collect([]);
        return view('assets.create', compact('autoAssetId', 'categories'))
            ->with('warning', 'Unable to load form data. Please ensure migrations are run: php artisan migrate --force');
    }
}

public function getFeaturesByBrand($brandId)
{
    $features = \App\Models\CategoryFeature::where('brand_id', $brandId)->get();
    return response()->json($features);
}

    /**
     * Get models for a brand (for Asset create model dropdown).
     */
    public function getModelsByBrand($brandId)
{
    $models = \App\Models\BrandModel::where('brand_id', $brandId)->orderBy('model_number')->get(['id', 'model_number']);
    return response()->json($models);
}

    /**
     * Get all models for a category (all brands in that category). For Asset create: select category → model → brand and features autofill.
     */
    public function getModelsByCategory($categoryId)
{
    $brands = \App\Models\Brand::where('asset_category_id', $categoryId)->orderBy('name')->get(['id', 'name']);
    $out = [];
    foreach ($brands as $brand) {
        $models = \App\Models\BrandModel::where('brand_id', $brand->id)->orderBy('model_number')->get(['id', 'model_number']);
        foreach ($models as $m) {
            $out[] = [
                'id' => $m->id,
                'model_number' => $m->model_number,
                'brand_id' => $brand->id,
                'brand_name' => $brand->name,
            ];
        }
    }
    return response()->json($out);
}

    /**
     * Get feature values for a model (to auto-fill feature fields in Asset create).
     */
    public function getModelFeatureValues($modelId)
{
    $model = \App\Models\BrandModel::with(['featureValues.categoryFeature', 'brand.features'])->find($modelId);
    if (!$model) {
        return response()->json([]);
    }
    $out = [];
    foreach ($model->featureValues as $fv) {
        $f = $fv->categoryFeature;
        if ($f->sub_fields && is_array($f->sub_fields) && count($f->sub_fields) > 0) {
            $decoded = @json_decode($fv->feature_value, true);
            if (is_array($decoded)) {
                $out[$f->id] = $decoded;
            } else {
                $out[$f->id] = [];
            }
        } else {
            $out[$f->id] = $fv->feature_value ?? '';
        }
    }
    return response()->json($out);
}

/**
 * Get category prefix for asset ID generation
 */
private function getCategoryPrefix($categoryName)
{
    $categoryName = strtolower(trim($categoryName));
    
    $prefixMap = [
        // Compute & infrastructure
        'server' => 'SRV',
        'desktop' => 'DTP',
        'laptop' => 'LPT',
        'workstation' => 'WKS',
        'tablet' => 'TAB',
        'tablet / ipad' => 'TAB',
        'mobile phone' => 'MOB',
        // Network
        'router' => 'RTR',
        'firewall' => 'FWL',
        'managed switch' => 'SWM',
        'unmanaged switch' => 'SWU',
        'poe switch' => 'SWP',
        'access point' => 'APN',
        'range extender' => 'EXT',
        '4g/5g router' => 'R4G',
        'load balancer' => 'LDB',
        // Storage
        'nas storage' => 'NAS',
        'san storage' => 'SAN',
        'external hard disk' => 'EHD',
        'internal hdd/ssd' => 'HDD',
        'backup device / tape' => 'BKP',
        'backup device' => 'BKP',
        // Print & scan
        'printer' => 'PRN',
        'plotter' => 'PLT',
        'scanner' => 'SCN',
        'all-in-one printer' => 'MFP',
        'mfp' => 'MFP',
        // Power & rack
        'ups' => 'UPS',
        'pdu' => 'PDU',
        'server rack' => 'RCK',
        'rack accessories' => 'RAC',
        // Telecom
        'pabx' => 'PBX',
        'telephone' => 'TEL',
        'video conferencing' => 'VCS',
        // AV & display
        'interactive panel / smart tv' => 'IPT',
        'interactive panel' => 'IPT',
        'smart tv' => 'IPT',
        'cctv camera' => 'CCTV',
        'nvr / dvr' => 'NVR',
        'nvr' => 'NVR',
        'dvr' => 'NVR',
        'projector' => 'PRJ',
        'monitor' => 'MON',
        // Peripherals
        'keyboard' => 'KBD',
        'mouse' => 'MSE',
        'docking station' => 'DCK',
        'webcam' => 'WBC',
        'headset' => 'HDS',
        'headphone' => 'HDS',
        // Virtual & software
        'virtual machine' => 'VM',
        'license' => 'LIC',
        'cloud subscription' => 'CLD',
        'ssl certificate' => 'SSL',
        'domain' => 'DOM',
        'public ip' => 'PIP',
        // Other hardware
        'kvm switch' => 'KVM',
        'kvm' => 'KVM',
        'network cable' => 'CAB',
        'sfp module' => 'SFP',
        'dr site' => 'DRS',
        // Legacy/alternate names
        'camera' => 'CCTV',
        'speaker' => 'SPK',
        'hard drive' => 'HDD',
        'ssd' => 'HDD',
    ];
    
    // Check exact match first
    if (isset($prefixMap[$categoryName])) {
        return $prefixMap[$categoryName];
    }
    
    // Check partial match
    foreach ($prefixMap as $key => $prefix) {
        if (str_contains($categoryName, $key) || str_contains($key, $categoryName)) {
            return $prefix;
        }
    }
    
    // Default: use first 3 uppercase letters of category name
    return strtoupper(substr(preg_replace('/[^a-z]/i', '', $categoryName), 0, 3));
}

/**
 * Get next asset ID for a category
 */
public function getNextAssetId($categoryId)
{
    try {
        $category = AssetCategory::find($categoryId);
        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }
        
        $prefix = $this->getCategoryPrefix($category->category_name);
        
        // Get the last asset with this prefix
        $lastAsset = Asset::where('asset_id', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(asset_id, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
            ->first();
        
        if ($lastAsset) {
            // Extract the number part
            $numberPart = preg_replace('/[^0-9]/', '', substr($lastAsset->asset_id, strlen($prefix)));
            $nextNumber = intval($numberPart) + 1;
        } else {
            $nextNumber = 1;
        }
        
        $nextAssetId = $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        
        return response()->json(['asset_id' => $nextAssetId]);
    } catch (\Exception $e) {
        Log::error('Error getting next asset ID: ' . $e->getMessage());
        return response()->json(['error' => 'Error generating asset ID'], 500);
    }
}

/**
 * Autocomplete endpoint for serial numbers
 */
public function autocompleteSerialNumber(Request $request)
{
    try {
        $query = trim($request->get('term', $request->get('q', '')));
        
        if (empty($query)) {
            return response()->json([]);
        }
        
        $serialNumbers = Asset::where('serial_number', 'LIKE', $query . '%')
            ->distinct()
            ->orderBy('serial_number', 'asc')
            ->limit(20)
            ->pluck('serial_number')
            ->values();
        
        return response()->json($serialNumbers);
    } catch (\Exception $e) {
        Log::error('Error in serial number autocomplete: ' . $e->getMessage());
        return response()->json([]);
    }
}

   public function assetsByCategory($id)
{
    $category = AssetCategory::findOrFail($id);
    $assets = Asset::with('category', 'brand')
                ->where('asset_category_id', $id)
                ->get();

    return view('assets.by_category', compact('category', 'assets'));
}

public function searchBySerialNumber(Request $request)
{
    $q = trim($request->get('q', ''));
    if (strlen($q) < 1) {
        return response()->json([]);
    }
    $assets = Asset::with(['assetCategory', 'brand'])
        ->where('serial_number', 'LIKE', $q . '%')
        ->orderBy('serial_number')
        ->limit(15)
        ->get()
        ->map(function ($asset) {
            return [
                'id' => $asset->id,
                'asset_id' => $asset->asset_id ?? 'N/A',
                'serial_number' => $asset->serial_number ?? 'N/A',
                'category_name' => $asset->assetCategory->category_name ?? 'N/A',
            ];
        });
    return response()->json($assets);
}

public function getAssetsByCategoryApi($id)
{
    $assets = Asset::with(['category', 'brand', 'featureValues.feature'])
                ->where('asset_category_id', $id)
                ->get()
                ->map(function($asset) {
                    // Format features
                    $features = [];
                    foreach ($asset->featureValues as $fv) {
                        $featureName = $fv->feature->feature_name ?? 'N/A';
                        $featureValue = $fv->feature_value ?? 'N/A';
                        $features[] = $featureName . ': ' . $featureValue;
                    }
                    
                    return [
                        'id' => $asset->id,
                        'asset_id' => $asset->asset_id ?? 'N/A',
                        'brand_name' => $asset->brand->name ?? 'N/A',
                        'purchase_date' => $asset->purchase_date ?? 'N/A',
                        'warranty_start' => $asset->warranty_start ?? 'N/A',
                        'expiry_date' => $asset->expiry_date ?? 'N/A',
                        'po_number' => $asset->po_number ?? 'N/A',
                        'serial_number' => $asset->serial_number ?? 'N/A',
                        'features' => $features,
                        'invoice_path' => $asset->invoice_path ?? null,
                    ];
                });

    return response()->json($assets);
}

public function exportByCategory($id, Request $request)
{
    $category = AssetCategory::findOrFail($id);
    $assets = Asset::with('category', 'brand')
                ->where('asset_category_id', $id)
                ->get();

    $format = $request->get('format', 'pdf');

    if ($format === 'excel' || $format === 'csv') {
        return $this->exportCategoryExcel($assets, $category);
    } else {
        return $this->exportCategoryPdf($assets, $category);
    }
}

private function exportCategoryPdf($assets, $category)
{
    $pdf = \PDF::loadView('assets.export-category-pdf', compact('assets', 'category'));
    return $pdf->download('assets-category-' . $category->category_name . '-' . date('Y-m-d') . '.pdf');
}

private function exportCategoryExcel($assets, $category)
{
    $filename = 'assets-category-' . $category->category_name . '-' . date('Y-m-d') . '.csv';
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ];

    $callback = function() use ($assets) {
        $file = fopen('php://output', 'w');
        
        // Headers
        fputcsv($file, [
            '#', 'Asset ID', 'Brand', 'Purchase Date', 'Warranty Start', 
            'Expiry Date', 'PO Number', 'Serial Number'
        ]);

        // Data
        foreach ($assets as $index => $asset) {
            fputcsv($file, [
                $index + 1,
                $asset->asset_id ?? 'N/A',
                $asset->brand->name ?? 'N/A',
                $asset->purchase_date ?? 'N/A',
                $asset->warranty_start ?? 'N/A',
                $asset->expiry_date ?? 'N/A',
                $asset->po_number ?? 'N/A',
                $asset->serial_number ?? 'N/A',
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

public function store(Request $request)
{
    try {
        // Test database connection first
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            Log::error('Asset store: Database connection failed: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Database connection failed. Please check your database credentials in Laravel Cloud environment variables.']);
        }

        // Check if required tables exist
        try {
            if (!Schema::hasTable('assets')) {
                Log::error('assets table does not exist');
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['error' => 'Database table not found. Please run migrations: php artisan migrate --force']);
            }
        } catch (\Exception $e) {
            Log::error('Asset store: Schema check failed: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Unable to check database tables. Please verify database connection.']);
        }

        // Calculate expiry date if warranty_start and warranty_years are provided
        if ($request->warranty_start && $request->warranty_years) {
            $warrantyYears = (int) $request->warranty_years; // Convert to integer
            $expiryDate = \Carbon\Carbon::parse($request->warranty_start)
                ->addYears($warrantyYears)
                ->format('Y-m-d');
            $request->merge(['expiry_date' => $expiryDate]);
        }

        // Generate asset_id based on category if not provided or empty
        if (empty($request->asset_id) && $request->asset_category_id) {
            try {
                $category = AssetCategory::find($request->asset_category_id);
                if ($category) {
                    $prefix = $this->getCategoryPrefix($category->category_name);
                    
                    // Get the last asset with this prefix
                    $lastAsset = Asset::where('asset_id', 'like', $prefix . '%')
                        ->orderByRaw('CAST(SUBSTRING(asset_id, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
                        ->first();
                    
                    if ($lastAsset) {
                        // Extract the number part
                        $numberPart = preg_replace('/[^0-9]/', '', substr($lastAsset->asset_id, strlen($prefix)));
                        $nextNumber = intval($numberPart) + 1;
                    } else {
                        $nextNumber = 1;
                    }
                    
                    $request->merge(['asset_id' => $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT)]);
                }
            } catch (\Exception $e) {
                Log::warning('Error generating asset_id: ' . $e->getMessage());
            }
        }

        // Build validation rules - make exists rules conditional
        $rules = [
            'asset_id' => 'required|unique:assets,asset_id',
            'purchase_date' => 'required|date',
            'warranty_start' => 'required|date',
            'warranty_years' => 'nullable|integer|min:1',
            'expiry_date' => 'nullable|date',
            'po_number' => 'nullable|string',
            'serial_number' => 'required|string|max:100',
            'invoice' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'features' => 'nullable|array',
            'features.*' => 'nullable',
        ];

        // Only add exists rules if tables exist
        if (Schema::hasTable('asset_categories')) {
            $rules['asset_category_id'] = 'required|exists:asset_categories,id';
        } else {
            $rules['asset_category_id'] = 'required';
        }

        if (Schema::hasTable('brands')) {
            $rules['brand_id'] = 'required|exists:brands,id';
        } else {
            $rules['brand_id'] = 'required';
        }

        $request->validate($rules);

        // Save the invoice if provided
        $invoicePath = null;
        if ($request->hasFile('invoice')) {
            $invoicePath = $request->file('invoice')->store('invoices', 'public');
        }

        // Create the asset
        $assetData = [
            'asset_id' => $request->asset_id,
            'asset_category_id' => $request->asset_category_id,
            'brand_id' => $request->brand_id,
            'purchase_date' => $request->purchase_date,
            'warranty_start' => $request->warranty_start,
            'warranty_years' => $request->warranty_years,
            'expiry_date' => $request->expiry_date,
            'po_number' => $request->po_number,
            'serial_number' => $request->serial_number,
            'status' => 'available', // Set default status
        ];

        if ($invoicePath) {
            $assetData['invoice_path'] = $invoicePath;
        }

        Log::info('Creating asset with data:', $assetData);
        
        $asset = Asset::create($assetData);
        
        Log::info('Asset created successfully. ID: ' . $asset->id);
        
        // Verify the asset was actually saved
        $savedAsset = Asset::find($asset->id);
        if (!$savedAsset) {
            Log::error('Asset was not saved to database!');
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to save asset. Please try again.']);
        }

        // Save features if provided and table exists
        if ($request->has('features') && is_array($request->features)) {
            // Check if category_feature_values table exists
            if (Schema::hasTable('category_feature_values')) {
                try {
                    foreach ($request->features as $featureId => $value) {
                        if (!empty($featureId)) {
                            // Check if value is an array (sub-fields like Storage)
                            if (is_array($value)) {
                                // For sub-fields, combine them into a single value string
                                $subFieldValues = [];
                                foreach ($value as $subField => $subValue) {
                                    if (!empty($subValue)) {
                                        $subFieldValues[] = $subField . ': ' . $subValue;
                                    }
                                }
                                if (!empty($subFieldValues)) {
                                    $combinedValue = implode(', ', $subFieldValues);
                                    \DB::table('category_feature_values')->insert([
                                        'asset_id' => $asset->id,
                                        'category_feature_id' => $featureId,
                                        'feature_value' => $combinedValue,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                }
                            } else {
                                // Regular single value
                                if (!empty($value)) {
                                    \DB::table('category_feature_values')->insert([
                                        'asset_id' => $asset->id,
                                        'category_feature_id' => $featureId,
                                        'feature_value' => $value,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to save feature values: ' . $e->getMessage());
                    // Continue - asset is saved, features are optional
                }
            } else {
                Log::warning('category_feature_values table does not exist. Feature values not saved. Run migrations: php artisan migrate --force');
            }
        }

        return redirect()->back()->with('success', 'Asset saved successfully!');

    } catch (\Illuminate\Validation\ValidationException $e) {
        throw $e; // Let Laravel handle validation errors
    } catch (\Illuminate\Database\QueryException $e) {
        Log::error('Asset store database error: ' . $e->getMessage());
        Log::error('Query error code: ' . $e->getCode());
        return redirect()
            ->back()
            ->withInput()
            ->withErrors(['error' => 'Database error occurred. Please ensure migrations are run: php artisan migrate --force']);
    } catch (\Throwable $e) {
        Log::error('Asset save error: ' . $e->getMessage());
        Log::error('Error class: ' . get_class($e));
        Log::error('Stack trace: ' . $e->getTraceAsString());
        Log::error('File: ' . $e->getFile() . ':' . $e->getLine());
        return redirect()->back()->with('error', 'Failed to save asset: ' . $e->getMessage())->withInput();
    }
}
public function locationAssets(Request $request)
{
    $locationName = $request->input('location_name');
    if (!$locationName) {
        return back()->with('error', 'No location name provided.');
    }

    // Find location by name (exact match or use 'like' for partial)
    $location = \App\Models\Location::where('location_name', $locationName)->first();

    if (!$location) {
        return back()->with('error', 'Location not found.');
    }

    // Get asset IDs assigned to this location (in asset_transactions table)
    $assetIds = \App\Models\AssetTransaction::where('location_id', $location->id)
        ->pluck('asset_id')
        ->unique();

    // Get assets with these IDs
    $assets = \App\Models\Asset::whereIn('id', $assetIds)
        ->with(['category', 'brand', 'latestTransaction'])
        ->get();

    return view('assets.location_assets', compact('assets'));
}
public function showRepairForm(Request $request)
{
    $employee = \App\Models\Employee::findOrFail($request->employee);
    $asset = \App\Models\Asset::findOrFail($request->asset);

    return view('asset_transactions.repair_form', compact('employee', 'asset'));
}
public function saveRepair(Request $request)
{
    $request->validate([
        'employee_id' => 'required|exists:employees,id',
        'asset_id' => 'required|exists:assets,id',
        'receive_date' => 'required|date',
        'repair_cost' => 'required|numeric',
        'repair_vendor' => 'required|string',
        'repair_type' => 'required|string',
        'remarks' => 'nullable|string',
    ]);

    \App\Models\AssetTransaction::create([
        'transaction_type' => 'repair',
        'employee_id' => $request->employee_id,
        'asset_id' => $request->asset_id,
        'receive_date' => $request->receive_date,
        'repair_cost' => $request->repair_cost,
        'repair_vendor' => $request->repair_vendor,
        'repair_type' => $request->repair_type,
        'remarks' => $request->remarks,
    ]);

    return redirect()->route('asset-transactions.index')->with('success', 'Repair record saved.');
}
public function getAssetDetails($assetId)
{
    // Load asset with related employee and project (adjust relationships)
    $asset = Asset::with(['brand', 'employee', 'project'])->find($assetId);

    if (!$asset) {
        return response()->json(['error' => 'Asset not found'], 404);
    }

    return response()->json([
        'serial_number' => $asset->serial_number,
        'brand' => $asset->brand->name ?? 'N/A',
        'employee_name' => $asset->employee->name ?? null,
        'project_name' => $asset->project->project_name ?? null,
        'invoice' => $asset->invoice_path ?? null,
    ]);
}
public function getFullDetails($id)
{
    $asset = Asset::with('assetCategory', 'brand', 'employee', 'project')->find($id);
    return response()->json([
        'asset' => $asset,
        'invoice' => $asset->invoice_path, // assuming saved in DB
        'employee' => $asset->employee,
        'project' => $asset->project,
    ]);
}
public function getAssetsByEmployee($id)
{
    $employee = \App\Models\Employee::find($id);

    if (!$employee) {
        \Log::info("Employee not found: {$id}");
        return response()->json([]);
    }

    \Log::info("Getting assets for employee: {$id} ({$employee->name})");

    // Get all assets with status 'assigned'
    $assignedAssets = \App\Models\Asset::where('status', 'assigned')
        ->with(['category', 'brand', 'latestTransaction.location'])
        ->get();

    \Log::info("Total assigned assets: " . $assignedAssets->count());

    // Filter assets where the latest transaction is an 'assign' transaction with this employee_id
    $employeeAssets = $assignedAssets->filter(function($asset) use ($id) {
        $latestTxn = $asset->latestTransaction;
        $matches = $latestTxn 
            && $latestTxn->transaction_type === 'assign' 
            && $latestTxn->employee_id == $id;
        
        if ($matches) {
            \Log::info("Asset {$asset->id} ({$asset->asset_id}) matches for employee {$id}");
        }
        
        return $matches;
    });

    \Log::info("Assets matching employee {$id}: " . $employeeAssets->count());

    // Format the response
    $assets = $employeeAssets->map(function ($asset) {
        $latestTxn = $asset->latestTransaction;
        return [
            'asset_id' => $asset->asset_id ?? '-',
            'category' => $asset->category ? $asset->category->category_name : '-',
            'brand' => $asset->brand ? $asset->brand->name : '-',
            'serial_number' => $asset->serial_number ?? '-',
            'po_number' => $asset->po_number ?? '-',
            'location' => $latestTxn && $latestTxn->location ? $latestTxn->location->location_name : '-',
            'issue_date' => $latestTxn ? ($latestTxn->issue_date ?? '-') : '-',
            'status' => ucfirst($asset->status ?? 'N/A'),
        ];
    })->values();

    \Log::info("Returning " . $assets->count() . " assets");
    return response()->json($assets);
}
public function getAssetsByLocation($id)
{
    $location = \App\Models\Location::with(['assets.category', 'assets.brand'])
        ->find($id);
    if (!$location) {
        return response()->json([]);
    }

    $assets = $location->assets->map(function ($asset) {
        return [
            'asset_id' => $asset->asset_id ?? '-',
            'category' => $asset->category->category_name ?? '-',
            'brand' => $asset->brand->name ?? '-',
            'serial_number' => $asset->serial_number ?? '-',
            'po_number' => $asset->po_number ?? '-',
            'purchase_date' => $asset->purchase_date ?? '-',
            'expiry_date' => $asset->expiry_date ?? '-',
        ];
    });

    return response()->json($assets);
}




}

