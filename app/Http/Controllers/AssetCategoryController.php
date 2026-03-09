<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\AssetCategory;
use App\Models\Brand;
use App\Models\BrandModel;
use App\Models\ModelFeatureValue;
use App\Models\CategoryFeature;
use App\Models\Asset;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class AssetCategoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Check if required tables exist
            $hasAssetCategories = Schema::hasTable('asset_categories');
            $hasAssets = Schema::hasTable('assets');
            
            if (!$hasAssetCategories && !$hasAssets) {
                Log::warning('asset_categories and assets tables do not exist');
                $categories = collect([]);
                $assets = collect([]);
                return view('categories.manage', compact('categories', 'assets'))
                    ->with('warning', 'Database tables not found. Please run migrations: php artisan migrate --force');
            }

            // Try to get categories, fallback to empty collection if table doesn't exist
            try {
                $categories = $hasAssetCategories 
                    ? AssetCategory::with(['brands.features', 'brands.models.featureValues'])->get() 
                    : collect([]);
            } catch (\Exception $e) {
                Log::warning('Error loading categories: ' . $e->getMessage());
                $categories = collect([]);
            }

            // Show only selected category (e.g. Printer → only printer brands/models; Laptop → only laptop)
            $selectedCategoryId = $request->filled('category_id') ? (int) $request->category_id : null;
            $selectedBrandId = $request->filled('brand_id') ? (int) $request->brand_id : null;
            $categoriesToShow = $categories;
            if ($selectedCategoryId) {
                $categoriesToShow = $categories->where('id', $selectedCategoryId)->values();
            }

            // Try to get assets, fallback to empty collection if table doesn't exist
            try {
                $assets = $hasAssets 
                    ? Asset::with(['latestTransaction.location', 'assetCategory'])->get() 
                    : collect([]);
            } catch (\Exception $e) {
                Log::warning('Error loading assets: ' . $e->getMessage());
                $assets = collect([]);
            }

            // Inline "Set values" on this page: load model + feature values when set_values param is present
            $setValuesModel = null;
            $setValuesFeatures = collect([]);
            $setValuesByFeature = collect([]);
            if ($request->filled('set_values')) {
                $m = BrandModel::with(['brand.features', 'featureValues'])->find($request->set_values);
                if ($m) {
                    $setValuesModel = $m;
                    $setValuesFeatures = $m->brand->features;
                    $setValuesByFeature = $m->featureValues->keyBy('category_feature_id');
                }
            }

            return view('categories.manage', compact('categories', 'categoriesToShow', 'selectedCategoryId', 'selectedBrandId', 'assets', 'setValuesModel', 'setValuesFeatures', 'setValuesByFeature'));
        } catch (\Exception $e) {
            Log::error('AssetCategory index error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Return empty collections instead of crashing
            $categories = collect([]);
            $categoriesToShow = collect([]);
            $selectedCategoryId = null;
            $selectedBrandId = null;
            $assets = collect([]);
            $setValuesModel = null;
            $setValuesFeatures = collect([]);
            $setValuesByFeature = collect([]);
            return view('categories.manage', compact('categories', 'categoriesToShow', 'selectedCategoryId', 'selectedBrandId', 'assets', 'setValuesModel', 'setValuesFeatures', 'setValuesByFeature'))
                ->with('warning', 'Unable to load categories. Please ensure migrations are run: php artisan migrate --force');
        }
    }

    /**
     * Form 1: Add Brand & Model — select category, then add brand, models, and features.
     */
    public function addBrandModelPage(Request $request)
    {
        $categories = AssetCategory::orderBy('category_name')->get();
        $selectedCategoryId = $request->filled('category_id') ? (int) $request->category_id : null;
        $brands = collect([]);
        if ($selectedCategoryId) {
            $brands = Brand::where('asset_category_id', $selectedCategoryId)->with(['models', 'features'])->orderBy('name')->get();
        }
        return view('brand_management.add_brand_model', compact('categories', 'selectedCategoryId', 'brands'));
    }

    /**
     * Form 2: Model Values — select category → brand → model, then add/edit model feature values. Blank until category selected.
     */
    public function modelValuesPage(Request $request)
    {
        $categories = AssetCategory::orderBy('category_name')->get();
        $selectedCategoryId = $request->filled('category_id') ? (int) $request->category_id : null;
        $selectedBrandId = $request->filled('brand_id') ? (int) $request->brand_id : null;
        $selectedModelId = $request->filled('model_id') ? (int) $request->model_id : null;
        $brands = collect([]);
        $models = collect([]);
        $model = null;
        $features = collect([]);
        $valuesByFeature = collect([]);
        if ($selectedCategoryId) {
            $brands = Brand::where('asset_category_id', $selectedCategoryId)->orderBy('name')->get();
        }
        if ($selectedBrandId) {
            $models = BrandModel::where('brand_id', $selectedBrandId)->orderBy('model_number')->get();
        }
        if ($selectedModelId) {
            $model = BrandModel::with(['brand.features', 'featureValues'])->find($selectedModelId);
            if ($model) {
                $features = $model->brand->features;
                $valuesByFeature = $model->featureValues->keyBy('category_feature_id');
            }
        }
        return view('brand_management.model_values', compact('categories', 'selectedCategoryId', 'selectedBrandId', 'selectedModelId', 'brands', 'models', 'model', 'features', 'valuesByFeature'));
    }

    /**
     * Show form to import Category, Brand, Model from CSV (columns: CATEGORY, BRAND, MODEL).
     */
    public function showCategoryBrandModelImportForm()
    {
        return view('brand_management.import_category_brand_model');
    }

    /**
     * Import categories, brands, and models from CSV. Columns: CATEGORY, BRAND, MODEL.
     * Creates category if missing; then brand under that category if missing; then model under that brand if missing.
     * Optimized for production: extended time limit, in-memory cache for category/brand, single transaction.
     */
    public function importCategoryBrandModel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt',
        ]);

        // Prevent 504 Gateway Timeout: allow script to run up to 5 minutes (production often has 30–60s gateway limit)
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }
        if (function_exists('ini_set')) {
            @ini_set('max_execution_time', '300');
        }

        $file = $request->file('file');
        try {
            $path = $file->getRealPath();
            $content = file_get_contents($path);
            if ($content === false) {
                throw new \Exception('Could not read file.');
            }
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
            $tempPath = sys_get_temp_dir() . '/cbm_import_' . uniqid() . '.csv';
            file_put_contents($tempPath, $content);

            $handle = fopen($tempPath, 'r');
            if (!$handle) {
                @unlink($tempPath);
                throw new \Exception('Could not open file.');
            }

            $headers = [];
            $rowNum = 0;
            $createdCategories = 0;
            $createdBrands = 0;
            $createdModels = 0;
            // In-memory cache to avoid repeated DB lookups (reduces queries and speeds up import)
            $categoryCache = [];
            $brandCache = [];

            \DB::transaction(function () use ($handle, &$headers, &$rowNum, &$createdCategories, &$createdBrands, &$createdModels, &$categoryCache, &$brandCache) {
                while (($row = fgetcsv($handle)) !== false) {
                    $rowNum++;
                    if ($rowNum === 1) {
                        $headers = array_map(function ($h) {
                            return trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h));
                        }, $row);
                        continue;
                    }

                    $data = array_combine($headers, array_pad($row, count($headers), ''));
                    if (!$data || count(array_filter($row)) === 0) {
                        continue;
                    }

                    $normalize = function ($key) use ($data) {
                        $keys = array_keys($data);
                        foreach ($keys as $k) {
                            if (str_replace(' ', '', strtolower($k)) === str_replace(' ', '', strtolower($key))) {
                                return trim($data[$k] ?? '');
                            }
                        }
                        return trim($data[$key] ?? '');
                    };

                    $categoryName = $normalize('CATEGORY') ?: $normalize('Category');
                    $brandName = $normalize('BRAND') ?: $normalize('Brand');
                    $modelName = $normalize('MODEL') ?: $normalize('Model');

                    if (empty($categoryName)) {
                        continue;
                    }

                    $catKey = strtolower(trim($categoryName));
                    if (!isset($categoryCache[$catKey])) {
                        $category = AssetCategory::whereRaw('LOWER(TRIM(category_name)) = ?', [$catKey])->first();
                        if (!$category) {
                            $category = AssetCategory::create(['category_name' => trim($categoryName)]);
                            $createdCategories++;
                        }
                        $categoryCache[$catKey] = $category;
                    }
                    $category = $categoryCache[$catKey];

                    if (empty($brandName)) {
                        continue;
                    }

                    $brandKey = $category->id . '|' . strtolower(trim($brandName));
                    if (!isset($brandCache[$brandKey])) {
                        $brand = Brand::where('asset_category_id', $category->id)
                            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($brandName))])
                            ->first();
                        if (!$brand) {
                            $brand = Brand::create([
                                'asset_category_id' => $category->id,
                                'name' => trim($brandName),
                            ]);
                            $createdBrands++;
                        }
                        $brandCache[$brandKey] = $brand;
                    }
                    $brand = $brandCache[$brandKey];

                    if (empty($modelName) || strtolower(trim($modelName)) === 'no result') {
                        continue;
                    }

                    $modelNumber = trim($modelName);
                    $exists = BrandModel::where('brand_id', $brand->id)
                        ->whereRaw('LOWER(TRIM(model_number)) = ?', [strtolower($modelNumber)])
                        ->exists();
                    if (!$exists) {
                        BrandModel::create([
                            'brand_id' => $brand->id,
                            'model_number' => $modelNumber,
                        ]);
                        $createdModels++;
                    }
                }
            });

            fclose($handle);
            @unlink($tempPath);

            $message = "Import complete: {$createdCategories} category(ies), {$createdBrands} brand(s), {$createdModels} model(s) added.";
            return back()->with('success', $message);
        } catch (\Exception $e) {
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            if (isset($tempPath) && file_exists($tempPath)) {
                @unlink($tempPath);
            }
            Log::error('Category/Brand/Model import error: ' . $e->getMessage());
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function storeCategory(Request $request)
    {
        try {
            if (!Schema::hasTable('asset_categories')) {
                Log::error('asset_categories table does not exist');
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['error' => 'Database table not found. Please run migrations: php artisan migrate --force']);
            }

            $request->validate(['category_name' => 'required|string|unique:asset_categories,category_name']);
            
            $categoryData = $request->only('category_name');
            Log::info('Creating category with data:', $categoryData);
            
            $category = AssetCategory::create($categoryData);
            
            Log::info('Category created successfully. ID: ' . $category->id);
            
            // Verify the category was actually saved
            $savedCategory = AssetCategory::find($category->id);
            if (!$savedCategory) {
                Log::error('Category was not saved to database!');
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['error' => 'Failed to save category. Please try again.']);
            }
            
            // If coming from brand-management page, redirect there with new category selected
            if ($request->headers->get('referer') && str_contains($request->headers->get('referer'), 'brand-management')) {
                return redirect()->route('brand-management.add-brand-model', ['category_id' => $category->id])
                    ->with('success', 'Category "' . $category->category_name . '" added successfully!');
            }
            return redirect()->back()->with('success', 'Category added successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Category store database error: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Database error occurred. Please ensure migrations are run: php artisan migrate --force']);
        } catch (\Exception $e) {
            Log::error('Category store error: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while saving the category. Please try again.']);
        }
    }

    public function storeBrand(Request $request)
    {
        try {
            if (!Schema::hasTable('brands')) {
                Log::error('brands table does not exist');
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['error' => 'Database table not found. Please run migrations: php artisan migrate --force']);
            }

            $request->validate([
                'asset_category_id' => 'required|exists:asset_categories,id',
                'name' => 'required|string'
            ]);

            $brand = Brand::create($request->only('asset_category_id', 'name'));
            
            // Check if category is Laptop and add default features
            try {
                $category = AssetCategory::find($request->asset_category_id);
                if ($category && strtolower(trim($category->category_name)) === 'laptop') {
                    $this->addDefaultLaptopFeatures($brand);
                }
            } catch (\Exception $e) {
                Log::warning('Error adding default laptop features: ' . $e->getMessage());
                // Continue even if features can't be added
            }
            
            return redirect()->back()->with('success', 'Brand added successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Brand store database error: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Database error occurred. Please ensure migrations are run: php artisan migrate --force']);
        } catch (\Exception $e) {
            Log::error('Brand store error: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while saving the brand. Please try again.']);
        }
    }

    private function addDefaultLaptopFeatures($brand)
    {
        $defaultFeatures = [
            ['name' => 'Make'],
            ['name' => 'Model Number'],
            ['name' => 'Processor'],
            ['name' => 'RAM'],
            ['name' => 'RAM Model / Family'],
            ['name' => 'Storage'],
            ['name' => 'Hard Drive Type'],
            ['name' => 'Graphic Card'],
            ['name' => 'Card Size'],
            ['name' => 'Screen Size'],
        ];

        foreach ($defaultFeatures as $feature) {
            CategoryFeature::create([
                'brand_id' => $brand->id,
                'feature_name' => $feature['name'],
                'sub_fields' => $feature['sub_fields'] ?? null,
                'asset_category_id' => $brand->asset_category_id,
            ]);
        }
    }

public function storeFeature(Request $request)
{
    $request->validate([
        'brand_id' => 'required|exists:brands,id',
        'feature_name' => 'required|string|max:255',
    ]);

    \App\Models\CategoryFeature::create([
        'brand_id' => $request->brand_id,
        'feature_name' => $request->feature_name,
        'asset_category_id' => $request->brand_id ? Brand::find($request->brand_id)->asset_category_id : null,
    ]);

    return back()->with('success', 'Feature added successfully.');
}
public function edit($id)
{
    $category = AssetCategory::findOrFail($id);
    return view('categories.edit', compact('category'));
}

public function update(Request $request, $id)
{
    $request->validate([
        'category_name' => 'required|string|max:255',
    ]);

    $category = AssetCategory::findOrFail($id);
    $category->update([
        'category_name' => $request->category_name,
    ]);

    return redirect()->route('categories.manage')->with('success', 'Category updated successfully.');
}

public function destroy($id)
{
    $category = AssetCategory::findOrFail($id);

    // Get all brands under this category
    $brands = Brand::where('asset_category_id', $category->id)->get();

    // Delete category features linked to these brands
    foreach ($brands as $brand) {
        \App\Models\CategoryFeature::where('brand_id', $brand->id)->delete();
    }

    // Delete brands
    Brand::where('asset_category_id', $category->id)->delete();

    // Delete category
    $category->delete();

    return redirect()->route('categories.manage')->with('success', 'Category and all related data deleted successfully.');
}

public function manageCategories()
{
    $categories = AssetCategory::with(['brands.features'])->get(); // Make sure relationships are defined
    return view('categories.manage', compact('categories'));
}

public function export($id, Request $request)
{
    $category = AssetCategory::with(['brands.features'])->findOrFail($id);
    $format = $request->get('format', 'pdf');

    if ($format === 'excel' || $format === 'csv') {
        return $this->exportCategoryExcel($category);
    } else {
        return $this->exportCategoryPdf($category);
    }
}

private function exportCategoryPdf($category)
{
    $pdf = \PDF::loadView('categories.export-pdf', compact('category'));
    return $pdf->download('category-' . str_replace(' ', '-', $category->category_name) . '-' . date('Y-m-d') . '.pdf');
}

private function exportCategoryExcel($category)
{
    $filename = 'category-' . str_replace(' ', '-', $category->category_name) . '-' . date('Y-m-d') . '.csv';
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ];

    $callback = function() use ($category) {
        $file = fopen('php://output', 'w');
        
        // Headers
        fputcsv($file, [
            'Category', 'Brand', 'Feature Name', 'Sub Fields'
        ]);

        // Data
        foreach ($category->brands as $brand) {
            if ($brand->features->count() > 0) {
                foreach ($brand->features as $feature) {
                    $subFields = '';
                    if ($feature->sub_fields && is_array($feature->sub_fields) && count($feature->sub_fields) > 0) {
                        $subFields = implode(', ', $feature->sub_fields);
                    }
                    
                    fputcsv($file, [
                        $category->category_name,
                        $brand->name,
                        $feature->feature_name,
                        $subFields
                    ]);
                }
            } else {
                // Brand with no features
                fputcsv($file, [
                    $category->category_name,
                    $brand->name,
                    'No features',
                    ''
                ]);
            }
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

    public function storeModel(Request $request)
    {
        $request->validate([
            'brand_id' => 'required|exists:brands,id',
            'model_number' => 'required|string|max:255',
        ]);
        BrandModel::create($request->only('brand_id', 'model_number'));
        return redirect()->back()->with('success', 'Model added successfully.');
    }

    public function editModelFeatures($id)
    {
        $model = BrandModel::with(['brand.features', 'featureValues.categoryFeature'])->findOrFail($id);
        $features = $model->brand->features;
        $valuesByFeature = $model->featureValues->keyBy('category_feature_id');
        return view('brand-models.edit-features', compact('model', 'features', 'valuesByFeature'));
    }

    public function updateModelFeatureValues(Request $request, $id)
    {
        $model = BrandModel::with('brand.features')->findOrFail($id);
        $features = $model->brand->features;
        foreach ($features as $feature) {
            $value = null;
            if ($feature->sub_fields && is_array($feature->sub_fields) && count($feature->sub_fields) > 0) {
                $sub = [];
                foreach ($feature->sub_fields as $subField) {
                    $key = 'features_' . $feature->id . '_' . $subField;
                    $sub[$subField] = $request->input($key, '');
                }
                $value = json_encode($sub);
            } else {
                $value = $request->input('features_' . $feature->id, '');
            }
            ModelFeatureValue::updateOrCreate(
                ['brand_model_id' => $model->id, 'category_feature_id' => $feature->id],
                ['feature_value' => $value]
            );
        }
        if (request()->filled('return_to') && request('return_to') === 'model_values') {
            $params = ['category_id' => request('category_id'), 'brand_id' => $model->brand_id, 'model_id' => $model->id];
            return redirect()->route('brand-management.model-values', $params)->with('success', 'Model feature values saved.');
        }
        $params = ['set_values' => $model->id];
        if (request()->filled('category_id')) {
            $params['category_id'] = request('category_id');
        }
        return redirect()->route('categories.manage', $params)->with('success', 'Model feature values saved.');
    }

    public function destroyModel($id)
    {
        $model = BrandModel::findOrFail($id);
        $model->delete();
        return redirect()->back()->with('success', 'Model deleted.');
    }
}
