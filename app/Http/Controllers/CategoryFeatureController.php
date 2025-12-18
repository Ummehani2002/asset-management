<?php
namespace App\Http\Controllers;
use App\Models\CategoryFeature;
use Illuminate\Http\Request;
class CategoryFeatureController extends Controller
{
       public function getByCategory($categoryId)
    {
        $features = CategoryFeature::where('asset_category_id', $categoryId)->get();
        return response()->json($features);
    }

   
public function getFeaturesByBrand($brandId)
{
    $features = \App\Models\CategoryFeature::where('brand_id', $brandId)->get();
    return response()->json($features);
}
public function edit($id)
{
    $feature = \App\Models\CategoryFeature::findOrFail($id);
    return view('features.edit', compact('feature'));
}

public function update(Request $request, $id)
{
    $request->validate([
        'feature_name' => 'required|string|max:255',
    ]);

    $feature = \App\Models\CategoryFeature::findOrFail($id);
    $feature->update([
        'feature_name' => $request->feature_name,
    ]);

    return redirect()->route('categories.manage')->with('success', 'Feature updated successfully.');
}

public function destroy($id)
{
    \App\Models\CategoryFeature::destroy($id);
    return back()->with('success', 'Feature deleted.');
}

}