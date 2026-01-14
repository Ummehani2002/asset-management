<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\AssetCategory;
use App\Models\Brand;
use App\Models\CategoryFeature;
use App\Models\Asset;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class AssetCategoryController extends Controller
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
                return view('categories.manage', compact('categories', 'assets'))
                    ->with('warning', 'Database tables not found. Please run migrations: php artisan migrate --force');
            }

            // Try to get categories, fallback to empty collection if table doesn't exist
            try {
                $categories = $hasAssetCategories 
                    ? AssetCategory::with(['brands.features'])->get() 
                    : collect([]);
            } catch (\Exception $e) {
                Log::warning('Error loading categories: ' . $e->getMessage());
                $categories = collect([]);
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

            return view('categories.manage', compact('categories', 'assets'));
        } catch (\Exception $e) {
            Log::error('AssetCategory index error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Return empty collections instead of crashing
            $categories = collect([]);
            $assets = collect([]);
            return view('categories.manage', compact('categories', 'assets'))
                ->with('warning', 'Unable to load categories. Please ensure migrations are run: php artisan migrate --force');
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
            ['name' => 'Brand'],
            ['name' => 'Model Number'],
            ['name' => 'Processor'],
            ['name' => 'RAM'],
            ['name' => 'Storage', 'sub_fields' => ['NVMI', 'PLC3', 'SATA']],
            ['name' => 'Graphic Card'],
            ['name' => 'Display']
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
}
