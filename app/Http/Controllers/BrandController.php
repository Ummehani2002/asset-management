<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class BrandController extends Controller
{
    
public function index()
    {
        try {
            // Check if brands table exists
            if (!Schema::hasTable('brands')) {
                Log::warning('brands table does not exist');
                $brands = collect([]);
                return view('brands.index', compact('brands'))
                    ->with('warning', 'Database tables not found. Please run migrations: php artisan migrate --force');
            }

            $brands = Brand::all(); // get all brands
            return view('brands.index', compact('brands')); // show brands in a view
        } catch (\Exception $e) {
            Log::error('Brand index error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Return empty list instead of crashing
            $brands = collect([]);
            return view('brands.index', compact('brands'))
                ->with('warning', 'Unable to load brands. Please ensure migrations are run: php artisan migrate --force');
        }
    }
       public function getByCategory($categoryId)
    {
        $brands = Brand::where('asset_category_id', $categoryId)->get();
        return response()->json($brands);
    }
    public function edit($id)
{
    $brand = \App\Models\Brand::findOrFail($id);
    return view('brands.edit', compact('brand'));
}

public function update(Request $request, $id)
{
    $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $brand = \App\Models\Brand::findOrFail($id);
    $brand->update([
        'name' => $request->name,
    ]);

    return redirect()->route('categories.manage')->with('success', 'Brand updated successfully.');
}

public function destroy($id)
{
    \App\Models\Brand::destroy($id);
    return back()->with('success', 'Brand deleted.');
}

}

