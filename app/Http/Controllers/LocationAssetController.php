<?php
namespace App\Http\Controllers;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LocationAssetController extends Controller
{
    public function index(Request $request)
    {
        try {
            $location = null;

            // Test database connection first
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                Log::error('LocationAsset: Database connection failed: ' . $e->getMessage());
                return view('location-assets', compact('location'))
                    ->with('error', 'Database connection failed. Please check your database credentials in Laravel Cloud environment variables.');
            }

            // Check if locations table exists
            try {
                if (!Schema::hasTable('locations')) {
                    Log::warning('LocationAsset: locations table does not exist');
                    return view('location-assets', compact('location'))
                        ->with('warning', 'Database tables not found. Please run migrations: php artisan migrate --force');
                }
            } catch (\Exception $e) {
                Log::error('LocationAsset: Schema check failed: ' . $e->getMessage());
                return view('location-assets', compact('location'))
                    ->with('error', 'Unable to check database tables. Please verify database connection.');
            }

            if ($request->filled('location_id')) {
                try {
                    $location = Location::with('assets.assetCategory')->find($request->location_id);
                    if (!$location) {
                        return view('location-assets', compact('location'))
                            ->with('error', 'Location not found.');
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    Log::error('LocationAsset: Query error: ' . $e->getMessage());
                    return view('location-assets', compact('location'))
                        ->with('error', 'Database query error. Please ensure migrations are run: php artisan migrate --force');
                } catch (\Exception $e) {
                    Log::error('LocationAsset: Error loading location: ' . $e->getMessage());
                    return view('location-assets', compact('location'))
                        ->with('error', 'Error loading location data. Please try again.');
                }
            }

            return view('location-assets', compact('location'));
        } catch (\Throwable $e) {
            Log::error('LocationAsset index fatal error: ' . $e->getMessage());
            Log::error('Error class: ' . get_class($e));
            Log::error('Stack trace: ' . $e->getTraceAsString());
            Log::error('File: ' . $e->getFile() . ':' . $e->getLine());
            
            $location = null;
            return view('location-assets', compact('location'))
                ->with('error', 'An error occurred. Please check Laravel Cloud logs for details.');
        }
    }

    public function autocomplete(Request $request)
    {
        try {
            // Test database connection
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                Log::error('LocationAsset autocomplete: Database connection failed');
                return response()->json([]);
            }

            // Check if table exists
            if (!Schema::hasTable('locations')) {
                Log::warning('LocationAsset autocomplete: locations table does not exist');
                return response()->json([]);
            }

            $query = $request->get('term', ''); // jQuery autocomplete uses "term"
            
            if (empty($query)) {
                return response()->json([]);
            }

            try {
                $locations = Location::where('location_name', 'LIKE', $query.'%')
                    ->orWhere('location_country', 'LIKE', $query.'%')
                    ->orWhere('location_entity', 'LIKE', $query.'%')
                    ->limit(10)
                    ->get(['id', 'location_name', 'location_country', 'location_entity']);

                return response()->json($locations);
            } catch (\Exception $e) {
                Log::error('LocationAsset autocomplete error: ' . $e->getMessage());
                return response()->json([]);
            }
        } catch (\Throwable $e) {
            Log::error('LocationAsset autocomplete fatal error: ' . $e->getMessage());
            return response()->json([]);
        }
    }
}

