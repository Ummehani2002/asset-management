<?php
namespace App\Http\Controllers;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationAssetController extends Controller
{
    public function index(Request $request)
    {
        $location = null;

        if ($request->filled('location_id')) {
            $location = Location::with('assets.assetCategory')->find($request->location_id);
            if (!$location) {
                return back()->with('error', 'Location not found.');
                // handle the case defnotr;y 
                
            }
        }

        return view('location-assets', compact('location'));
    }

  public function autocomplete(Request $request)
{
    $query = $request->get('term'); // jQuery autocomplete uses "term"
    $locations = Location::where('location_name', 'LIKE', $query.'%')
        ->orWhere('location_id', 'LIKE', $query.'%')
        ->limit(10)
        ->get(['location_id', 'location_name']);

    return response()->json($locations);
}

}

