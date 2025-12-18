<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
class BrandController extends Controller
{
    
public function index()
    {
        $brands = Brand::all(); // get all brands
        return view('brands.index', compact('brands')); // show brands in a view
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

