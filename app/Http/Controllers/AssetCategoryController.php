<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\AssetCategory;
use App\Models\Brand;
use App\Models\CategoryFeature;
use App\Models\Asset;

class AssetCategoryController extends Controller
{
    public function index()
    {
        try {
            // Check if required tables exist
            if (!$this->checkDatabaseTables(['asset_categories'])) {
                return redirect()->route('login')->withErrors(['error' => 'Database tables not found. Please run migrations: php artisan migrate --force']);
            }

            $categories = AssetCategory::with(['brands.features'])->get();
            $assets = Asset::with(['latestTransaction.location', 'assetCategory'])->get();
            return view('categories.manage', compact('categories', 'assets'));
        } catch (\Exception $e) {
            return $this->handleDatabaseError($e);
        }
    }
    public function storeCategory(Request $request)
    {
        $request->validate(['category_name' => 'required|string|unique:asset_categories,category_name']);
        AssetCategory::create($request->only('category_name'));
        return redirect()->back()->with('success', 'Category added successfully!');
    }

    public function storeBrand(Request $request)
    {
        $request->validate([
            'asset_category_id' => 'required|exists:asset_categories,id',
            'name' => 'required|string'
        ]);

        $brand = Brand::create($request->only('asset_category_id', 'name'));
        
        // Check if category is Laptop and add default features
        $category = AssetCategory::find($request->asset_category_id);
        if ($category && strtolower(trim($category->category_name)) === 'laptop') {
            $this->addDefaultLaptopFeatures($brand);
        }
        
        return redirect()->back()->with('success', 'Brand added successfully!');
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
