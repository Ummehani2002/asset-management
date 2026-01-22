<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AssetTransaction;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Project;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\AssetAssigned;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class AssetTransactionController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Check if required tables exist
            if (!Schema::hasTable('asset_transactions')) {
                Log::warning('asset_transactions table does not exist');
                $transactions = collect([]);
                return view('asset_transactions.index', compact('transactions'))
                    ->with('warning', 'Database tables not found. Please run migrations: php artisan migrate --force');
            }

            $query = AssetTransaction::with(['asset.assetCategory', 'employee', 'location']);

        // Filter by asset status - show all transactions for assets with this status
        // Default to showing only assigned assets if no filter is explicitly set
        $isAssignedFilter = false;
        $isMaintenanceFilter = false;
        $isAvailableFilter = false;
        if ($request->has('asset_status')) {
            // User has interacted with the filter
            if ($request->asset_status !== '') {
                // User selected a specific status
                $status = $request->asset_status;
                $isAssignedFilter = ($status === 'assigned');
                $isMaintenanceFilter = ($status === 'under_maintenance');
                $isAvailableFilter = ($status === 'available');
                $query->whereHas('asset', function($q) use ($status) {
                    $q->where('status', $status);
                });
            }
            // If asset_status is empty string, user selected "All Statuses" - show all
        } else {
            // First page load - default to showing only assigned assets
            $isAssignedFilter = true;
            $query->whereHas('asset', function($q) {
                $q->where('status', 'assigned');
            });
        }

        // For assigned assets, show only the latest assign transaction (current assignment)
        // Exclude maintenance and return transactions - only show the assign that made it assigned
        if ($isAssignedFilter) {
            // Get only assign transactions for currently assigned assets
            $query->where('transaction_type', 'assign');
            
            // Get only the latest assign transaction for each assigned asset
            // This represents the current assignment (not historical assignments)
            // Use a raw subquery to find IDs of latest assign transactions per asset
            $latestAssignIds = DB::table('asset_transactions as at1')
                ->select('at1.id')
                ->where('at1.transaction_type', 'assign')
                ->whereIn('at1.asset_id', function($q) {
                    $q->select('id')
                      ->from('assets')
                      ->where('status', 'assigned');
                })
                ->whereRaw('at1.created_at = (
                    SELECT MAX(at2.created_at)
                    FROM asset_transactions as at2
                    WHERE at2.asset_id = at1.asset_id
                    AND at2.transaction_type = "assign"
                )')
                ->pluck('id')
                ->toArray();
            
            if (!empty($latestAssignIds)) {
                $query->whereIn('asset_transactions.id', $latestAssignIds);
            } else {
                // If no assign transactions found, return empty result
                $query->whereRaw('1 = 0');
            }
        }

        // For assets under maintenance, show only the latest maintenance transaction (current maintenance)
        // Exclude assign and return transactions - only show the maintenance that made it under maintenance
        if ($isMaintenanceFilter) {
            // Get only maintenance transactions for currently under-maintenance assets
            $query->where('transaction_type', 'system_maintenance');
            
            // Get only the latest maintenance transaction for each under-maintenance asset
            // This represents the current maintenance (not historical maintenance)
            // Use a raw subquery to find IDs of latest maintenance transactions per asset
            $latestMaintenanceIds = DB::table('asset_transactions as at1')
                ->select('at1.id')
                ->where('at1.transaction_type', 'system_maintenance')
                ->whereIn('at1.asset_id', function($q) {
                    $q->select('id')
                      ->from('assets')
                      ->where('status', 'under_maintenance');
                })
                ->whereRaw('at1.created_at = (
                    SELECT MAX(at2.created_at)
                    FROM asset_transactions as at2
                    WHERE at2.asset_id = at1.asset_id
                    AND at2.transaction_type = "system_maintenance"
                )')
                ->pluck('id')
                ->toArray();
            
            if (!empty($latestMaintenanceIds)) {
                $query->whereIn('asset_transactions.id', $latestMaintenanceIds);
            } else {
                // If no maintenance transactions found, return empty result
                $query->whereRaw('1 = 0');
            }
        }

        // For available assets, show only the latest return transaction (current return)
        // Exclude assign and maintenance transactions - only show the return that made it available
        if ($isAvailableFilter) {
            // Get only return transactions for currently available assets
            $query->where('transaction_type', 'return');
            
            // Get only the latest return transaction for each available asset
            // This represents the current return (not historical returns)
            // Use a raw subquery to find IDs of latest return transactions per asset
            $latestReturnIds = DB::table('asset_transactions as at1')
                ->select('at1.id')
                ->where('at1.transaction_type', 'return')
                ->whereIn('at1.asset_id', function($q) {
                    $q->select('id')
                      ->from('assets')
                      ->where('status', 'available');
                })
                ->whereRaw('at1.created_at = (
                    SELECT MAX(at2.created_at)
                    FROM asset_transactions as at2
                    WHERE at2.asset_id = at1.asset_id
                    AND at2.transaction_type = "return"
                )')
                ->pluck('id')
                ->toArray();
            
            if (!empty($latestReturnIds)) {
                $query->whereIn('asset_transactions.id', $latestReturnIds);
            } else {
                // If no return transactions found, return empty result
                $query->whereRaw('1 = 0');
            }
        }

        // Filter by transaction type (only if not filtering by assigned, maintenance, or available)
        if ($request->filled('transaction_type') && !$isAssignedFilter && !$isMaintenanceFilter && !$isAvailableFilter) {
            $query->where('transaction_type', $request->transaction_type);
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('asset', function($assetQuery) use ($search) {
                    $assetQuery->where('serial_number', 'like', "%{$search}%")
                               ->orWhere('asset_id', 'like', "%{$search}%");
                })
                ->orWhereHas('employee', function($empQuery) use ($search) {
                    $empQuery->where('name', 'like', "%{$search}%")
                             ->orWhere('entity_name', 'like', "%{$search}%");
                })
                ->orWhere('project_name', 'like', "%{$search}%");
            });
        }

        $transactions = $query->orderByDesc('created_at')->paginate(25);

        return view('asset_transactions.index', compact('transactions'));
        } catch (\Exception $e) {
            Log::error('AssetTransaction index error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Return empty list instead of crashing
            $transactions = collect([]);
            return view('asset_transactions.index', compact('transactions'))
                ->with('warning', 'Unable to load transactions. Please ensure migrations are run: php artisan migrate --force');
        }
    }

    public function view(Request $request)
    {
        $query = AssetTransaction::with(['asset.assetCategory', 'employee', 'location']);

        // Filter by type
        if ($request->filled('filter')) {
            $filter = $request->filter;
            switch ($filter) {
                case 'assigned':
                    // Show assigned assets - assets with status 'assigned'
                    $query->whereHas('asset', function($q) {
                        $q->where('status', 'assigned');
                    });
                    break;
                case 'maintenance':
                    // Show maintenance transactions - either transaction type is maintenance OR asset status is under_maintenance
                    $query->where(function($q) {
                        $q->where('transaction_type', 'system_maintenance')
                          ->orWhereHas('asset', function($assetQuery) {
                              $assetQuery->where('status', 'under_maintenance');
                          });
                    });
                    break;
                case 'return':
                    // Show return transactions
                    $query->where('transaction_type', 'return');
                    break;
                case 'available':
                    // Show available assets - assets with status 'available'
                    $query->whereHas('asset', function($q) {
                        $q->where('status', 'available');
                    });
                    break;
            }
        }

        $transactions = $query->orderByDesc('created_at')->paginate(25);
        $currentFilter = $request->get('filter', '');

        return view('asset_transactions.view', compact('transactions', 'currentFilter'));
    }

    public function export(Request $request)
    {
        $query = AssetTransaction::with(['asset.assetCategory', 'employee', 'location']);

        // Check if user wants to download all transactions (ignoring filters)
        $downloadAll = $request->get('download_all', false);

        if (!$downloadAll) {
            // Apply the same filtering logic as index method
            // Filter by asset status - show all transactions for assets with this status
            $isAssignedFilter = false;
            $isMaintenanceFilter = false;
            $isAvailableFilter = false;
            
            if ($request->has('asset_status') && $request->asset_status !== '') {
                $status = $request->asset_status;
                $isAssignedFilter = ($status === 'assigned');
                $isMaintenanceFilter = ($status === 'under_maintenance');
                $isAvailableFilter = ($status === 'available');
                $query->whereHas('asset', function($q) use ($status) {
                    $q->where('status', $status);
                });
            } elseif (!$request->has('asset_status')) {
                // Default to assigned if no filter set
                $isAssignedFilter = true;
                $query->whereHas('asset', function($q) {
                    $q->where('status', 'assigned');
                });
            }

            // For assigned assets, show only the latest assign transaction (current assignment)
            if ($isAssignedFilter) {
                $query->where('transaction_type', 'assign');
                
                $latestAssignIds = DB::table('asset_transactions as at1')
                    ->select('at1.id')
                    ->where('at1.transaction_type', 'assign')
                    ->whereIn('at1.asset_id', function($q) {
                        $q->select('id')
                          ->from('assets')
                          ->where('status', 'assigned');
                    })
                    ->whereRaw('at1.created_at = (
                        SELECT MAX(at2.created_at)
                        FROM asset_transactions as at2
                        WHERE at2.asset_id = at1.asset_id
                        AND at2.transaction_type = "assign"
                    )')
                    ->pluck('id')
                    ->toArray();
                
                if (!empty($latestAssignIds)) {
                    $query->whereIn('asset_transactions.id', $latestAssignIds);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            // For assets under maintenance, show only the latest maintenance transaction
            if ($isMaintenanceFilter) {
                $query->where('transaction_type', 'system_maintenance');
                
                $latestMaintenanceIds = DB::table('asset_transactions as at1')
                    ->select('at1.id')
                    ->where('at1.transaction_type', 'system_maintenance')
                    ->whereIn('at1.asset_id', function($q) {
                        $q->select('id')
                          ->from('assets')
                          ->where('status', 'under_maintenance');
                    })
                    ->whereRaw('at1.created_at = (
                        SELECT MAX(at2.created_at)
                        FROM asset_transactions as at2
                        WHERE at2.asset_id = at1.asset_id
                        AND at2.transaction_type = "system_maintenance"
                    )')
                    ->pluck('id')
                    ->toArray();
                
                if (!empty($latestMaintenanceIds)) {
                    $query->whereIn('asset_transactions.id', $latestMaintenanceIds);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            // For available assets, show only the latest return transaction
            if ($isAvailableFilter) {
                $query->where('transaction_type', 'return');
                
                $latestReturnIds = DB::table('asset_transactions as at1')
                    ->select('at1.id')
                    ->where('at1.transaction_type', 'return')
                    ->whereIn('at1.asset_id', function($q) {
                        $q->select('id')
                          ->from('assets')
                          ->where('status', 'available');
                    })
                    ->whereRaw('at1.created_at = (
                        SELECT MAX(at2.created_at)
                        FROM asset_transactions as at2
                        WHERE at2.asset_id = at1.asset_id
                        AND at2.transaction_type = "return"
                    )')
                    ->pluck('id')
                    ->toArray();
                
                if (!empty($latestReturnIds)) {
                    $query->whereIn('asset_transactions.id', $latestReturnIds);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            // Filter by transaction type (only if not filtering by assigned, maintenance, or available)
            if ($request->filled('transaction_type') && !$isAssignedFilter && !$isMaintenanceFilter && !$isAvailableFilter) {
                $query->where('transaction_type', $request->transaction_type);
            }

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->whereHas('asset', function($assetQuery) use ($search) {
                        $assetQuery->where('serial_number', 'like', "%{$search}%")
                                   ->orWhere('asset_id', 'like', "%{$search}%");
                    })
                    ->orWhereHas('employee', function($empQuery) use ($search) {
                        $empQuery->where('name', 'like', "%{$search}%")
                                 ->orWhere('entity_name', 'like', "%{$search}%");
                    })
                    ->orWhere('project_name', 'like', "%{$search}%");
                });
            }
        }

        $transactions = $query->orderByDesc('created_at')->get();
        $format = $request->get('format', 'pdf');
        $assetStatus = $downloadAll ? 'all' : ($request->get('asset_status', 'all'));

        if ($format === 'excel' || $format === 'csv') {
            return $this->exportExcel($transactions, $assetStatus);
        } else {
            return $this->exportPdf($transactions, $assetStatus);
        }
    }

    private function exportPdf($transactions, $assetStatus)
    {
        $pdf = \PDF::loadView('asset_transactions.export-pdf', compact('transactions', 'assetStatus'));
        return $pdf->download('asset-transactions-' . ($assetStatus !== 'all' ? $assetStatus : 'all') . '-' . date('Y-m-d') . '.pdf');
    }

    private function exportExcel($transactions, $assetStatus)
    {
        $filename = 'asset-transactions-' . ($assetStatus !== 'all' ? $assetStatus : 'all') . '-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                '#', 'Transaction ID', 'Asset ID', 'Serial Number', 'Category', 'Transaction Type', 
                'Status', 'Employee/Project', 'Location', 'Issue Date', 'Return Date', 
                'Receive Date', 'Delivery Date', 'Created At'
            ]);

            // Data
            foreach ($transactions as $index => $t) {
                $assignedTo = $t->employee->name ?? $t->project_name ?? 'N/A';
                $status = $t->asset->status ?? 'N/A';
                
                fputcsv($file, [
                    $index + 1,
                    $t->id,
                    $t->asset->asset_id ?? 'N/A',
                    $t->asset->serial_number ?? 'N/A',
                    $t->asset->assetCategory->category_name ?? 'N/A',
                    ucfirst(str_replace('_', ' ', $t->transaction_type)),
                    $status,
                    $assignedTo,
                    $t->location->location_name ?? 'N/A',
                    $t->issue_date ?? 'N/A',
                    $t->return_date ?? 'N/A',
                    $t->receive_date ?? 'N/A',
                    $t->delivery_date ?? 'N/A',
                    $t->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function create()
    {
        try {
            // Initialize collections first
            $categories = collect([]);
            $assets = collect([]);
            $employees = collect([]);
            $locations = collect([]);
            $projects = collect([]);

            // Test database connection first
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                Log::error('AssetTransaction create: Database connection failed: ' . $e->getMessage());
                return view('asset_transactions.create', compact('categories', 'assets', 'employees', 'locations', 'projects'))
                    ->with('error', 'Database connection failed. Please check your database credentials in Laravel Cloud environment variables.');
            }

            // Check if required tables exist
            try {
                $hasAssetCategories = Schema::hasTable('asset_categories');
                $hasAssets = Schema::hasTable('assets');
                $hasEmployees = Schema::hasTable('employees');
                $hasLocations = Schema::hasTable('locations');
                $hasProjects = Schema::hasTable('projects');
            } catch (\Exception $e) {
                Log::error('AssetTransaction create: Schema check failed: ' . $e->getMessage());
                return view('asset_transactions.create', compact('categories', 'assets', 'employees', 'locations', 'projects'))
                    ->with('error', 'Unable to check database tables. Please verify database connection.');
            }
            
            // Get categories
            if ($hasAssetCategories) {
                try {
                    $categories = \App\Models\AssetCategory::all();
                    if (!$categories instanceof \Illuminate\Support\Collection) {
                        $categories = collect($categories);
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    Log::error('AssetTransaction create: Categories query error: ' . $e->getMessage());
                } catch (\Exception $e) {
                    Log::warning('Error loading categories: ' . $e->getMessage());
                }
            }
            
            // Get assets
            if ($hasAssets) {
                try {
                    $assets = Asset::with('assetCategory')->get();
                    if (!$assets instanceof \Illuminate\Support\Collection) {
                        $assets = collect($assets);
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    Log::error('AssetTransaction create: Assets query error: ' . $e->getMessage());
                } catch (\Exception $e) {
                    Log::warning('Error loading assets: ' . $e->getMessage());
                }
            }
            
            // Get employees
            if ($hasEmployees) {
                try {
                    $employees = Employee::all();
                    if (!$employees instanceof \Illuminate\Support\Collection) {
                        $employees = collect($employees);
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    Log::error('AssetTransaction create: Employees query error: ' . $e->getMessage());
                } catch (\Exception $e) {
                    Log::warning('Error loading employees: ' . $e->getMessage());
                }
            }
            
            // Get locations
            if ($hasLocations) {
                try {
                    $locations = Location::all();
                    if (!$locations instanceof \Illuminate\Support\Collection) {
                        $locations = collect($locations);
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    Log::error('AssetTransaction create: Locations query error: ' . $e->getMessage());
                } catch (\Exception $e) {
                    Log::warning('Error loading locations: ' . $e->getMessage());
                }
            }
            
            // Get projects
            if ($hasProjects) {
                try {
                    $projects = \App\Models\Project::all();
                    if (!$projects instanceof \Illuminate\Support\Collection) {
                        $projects = collect($projects);
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    Log::error('AssetTransaction create: Projects query error: ' . $e->getMessage());
                } catch (\Exception $e) {
                    Log::warning('Error loading projects: ' . $e->getMessage());
                }
            }

            $hasAllTables = $hasAssetCategories && $hasAssets && $hasEmployees && $hasLocations && $hasProjects;
            return view('asset_transactions.create', compact('categories', 'assets', 'employees', 'locations', 'projects'))
                ->with('warning', $hasAllTables ? null : 'Some database tables not found. Please run migrations: php artisan migrate --force');
        } catch (\Throwable $e) {
            Log::error('AssetTransaction create fatal error: ' . $e->getMessage());
            Log::error('Error class: ' . get_class($e));
            Log::error('Stack trace: ' . $e->getTraceAsString());
            Log::error('File: ' . $e->getFile() . ':' . $e->getLine());
            
            // Return with empty collections
            $categories = collect([]);
            $assets = collect([]);
            $employees = collect([]);
            $locations = collect([]);
            $projects = collect([]);
            return view('asset_transactions.create', compact('categories', 'assets', 'employees', 'locations', 'projects'))
                ->with('error', 'An error occurred. Please check Laravel Cloud logs for details.');
        }
    }

    public function maintenance()
    {
        $categories = \App\Models\AssetCategory::all();
        return view('asset_transactions.maintenance', compact('categories'));
    }

    public function maintenanceStore(Request $request)
    {
        $request->validate([
            'asset_category_id' => 'required|exists:asset_categories,id',
            'asset_id' => 'required|exists:assets,id',
            'receive_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:receive_date',
            'repair_type' => 'nullable|string',
            'maintenance_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'maintenance_notes' => 'nullable|string',
        ]);

        $asset = Asset::with('assetCategory')->findOrFail($request->asset_id);
        $latest = $asset->latestTransaction;
        
        // Validate that asset is assigned
        if ($asset->status !== 'assigned') {
            throw ValidationException::withMessages([
                'asset_id' => 'Only assigned assets can be sent for maintenance. Current status: ' . ucfirst($asset->status ?? 'unknown'),
            ]);
        }

        // Get employee from latest assignment
        $employeeForEmail = null;
        if ($latest && $latest->employee_id) {
            $employeeForEmail = Employee::find($latest->employee_id);
        }

        // Handle image upload
        $imageData = [];
        if ($request->hasFile('maintenance_image')) {
            $imageData['maintenance_image'] = $this->uploadImage($request->file('maintenance_image'), 'maintenance');
        }

        // Create maintenance transaction
        $transaction = AssetTransaction::create(array_merge([
            'asset_id' => $asset->id,
            'transaction_type' => 'system_maintenance',
            'status' => 'under_maintenance',
            'receive_date' => $request->receive_date,
            'delivery_date' => $request->delivery_date,
            'assigned_to_type' => $latest->assigned_to_type ?? 'employee',
            'employee_id' => $latest->employee_id,
            'project_name' => $latest->project_name,
            'location_id' => $latest->location_id,
            'repair_type' => $request->repair_type,
            'maintenance_notes' => $request->maintenance_notes,
        ], $imageData));

        // Update asset status
        $asset->update(['status' => 'under_maintenance']);

        // Send email to employee
        if ($employeeForEmail) {
            $transaction->employee_id = $employeeForEmail->id;
            $transaction->save();
            $this->sendAssetEmail($transaction);
        }

        return redirect()->route('asset-transactions.index')
            ->with('success', 'Asset sent for maintenance successfully! Email notification sent to employee.');
    }

    public function maintenanceReassign(Request $request)
    {
        $request->validate([
            'asset_category_id' => 'required|exists:asset_categories,id',
            'asset_id' => 'required|exists:assets,id',
            'action_type' => 'required|in:reassign,maintenance,return',
            'reassign_date' => 'required|date',
            'reassign_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'reassign_notes' => 'nullable|string',
            'receive_date' => 'nullable|date|required_if:action_type,maintenance',
            'delivery_date' => 'nullable|date|after_or_equal:receive_date',
            'repair_type' => 'nullable|string',
        ]);

        $asset = Asset::with('assetCategory')->findOrFail($request->asset_id);
        $latest = $asset->latestTransaction;
        
        // Validate that asset is under maintenance
        if ($asset->status !== 'under_maintenance') {
            throw ValidationException::withMessages([
                'asset_id' => 'Only assets under maintenance can be processed. Current status: ' . ucfirst($asset->status ?? 'unknown'),
            ]);
        }

        // Find the assignment before maintenance
        $beforeMaintenance = AssetTransaction::where('asset_id', $asset->id)
            ->where('transaction_type', 'assign')
            ->where('id', '<', $latest->id)
            ->latest()
            ->first();
        
        if (!$beforeMaintenance || !$beforeMaintenance->employee_id) {
            throw ValidationException::withMessages([
                'asset_id' => 'Cannot find previous assignment for this asset. Cannot process.',
            ]);
        }

        $employeeForEmail = Employee::find($beforeMaintenance->employee_id);
        
        if (!$employeeForEmail) {
            throw ValidationException::withMessages([
                'asset_id' => 'Employee not found for previous assignment.',
            ]);
        }

        $imageData = [];
        $transaction = null;
        $successMessage = '';

        // Handle different action types
        if ($request->action_type === 'reassign') {
            // Reassign to same employee
            if ($request->hasFile('reassign_image')) {
                $imageData['assign_image'] = $this->uploadImage($request->file('reassign_image'), 'assign');
            }

            $transaction = AssetTransaction::create(array_merge([
                'asset_id' => $asset->id,
                'transaction_type' => 'assign',
                'status' => 'assigned',
                'issue_date' => $request->reassign_date,
                'assigned_to_type' => 'employee',
                'employee_id' => $beforeMaintenance->employee_id,
                'project_name' => null,
                'location_id' => $beforeMaintenance->location_id,
                'maintenance_notes' => $request->reassign_notes,
            ], $imageData));

            $asset->update(['status' => 'assigned']);
            $successMessage = 'Asset reassigned to same employee successfully! Email notification sent. Asset is ready for collection.';

        } elseif ($request->action_type === 'maintenance') {
            // Send back to maintenance
            if ($request->hasFile('reassign_image')) {
                $imageData['maintenance_image'] = $this->uploadImage($request->file('reassign_image'), 'maintenance');
            }

            $transaction = AssetTransaction::create(array_merge([
                'asset_id' => $asset->id,
                'transaction_type' => 'system_maintenance',
                'status' => 'under_maintenance',
                'receive_date' => $request->receive_date,
                'delivery_date' => $request->delivery_date,
                'assigned_to_type' => $beforeMaintenance->assigned_to_type ?? 'employee',
                'employee_id' => $beforeMaintenance->employee_id,
                'project_name' => $beforeMaintenance->project_name,
                'location_id' => $beforeMaintenance->location_id,
                'repair_type' => $request->repair_type,
                'maintenance_notes' => $request->reassign_notes,
            ], $imageData));

            $asset->update(['status' => 'under_maintenance']);
            $successMessage = 'Asset sent back to maintenance successfully!';

        } elseif ($request->action_type === 'return') {
            // Return the asset
            if ($request->hasFile('reassign_image')) {
                $imageData['return_image'] = $this->uploadImage($request->file('reassign_image'), 'return');
            }

            $transaction = AssetTransaction::create(array_merge([
                'asset_id' => $asset->id,
                'transaction_type' => 'return',
                'status' => 'available',
                'return_date' => $request->reassign_date,
                'assigned_to_type' => null,
                'employee_id' => null,
                'project_name' => null,
                'location_id' => null,
                'maintenance_notes' => $request->reassign_notes,
            ], $imageData));

            $asset->update(['status' => 'available']);
            $successMessage = 'Asset returned successfully! Asset is now available.';
        }

        // Send email to employee (only for reassign)
        if ($transaction && $request->action_type === 'reassign') {
            $this->sendAssetEmail($transaction);
        }

        return redirect()->route('asset-transactions.index')
            ->with('success', $successMessage);
    }

    public function edit($id)
    {
        $transaction = AssetTransaction::with(['asset.assetCategory', 'employee'])->findOrFail($id);
        $categories = \App\Models\AssetCategory::all();
        $assets = Asset::with('assetCategory')->get();
        $employees = Employee::all();
        $locations = Location::all();
        $projects = \App\Models\Project::all();

        return view('asset_transactions.create', compact('transaction', 'categories', 'assets', 'employees', 'locations', 'projects'));
    }

    public function getAssetsByCategory($categoryId)
    {
        $assets = Asset::with('assetCategory')
            ->where('asset_category_id', $categoryId)
            ->get()
            ->map(function ($asset) {
                $originalStatus = $asset->status ?? 'available';
                // Display "available" instead of "returned" for UI, but keep original for logic
                $displayStatus = ($originalStatus === 'returned') ? 'available' : $originalStatus;
                
                return [
                    'id' => $asset->id,
                    'asset_id' => $asset->asset_id,
                    'serial_number' => $asset->serial_number,
                    'status' => $displayStatus, // Display status (available instead of returned)
                    'original_status' => $originalStatus, // Original status for logic
                    'category_name' => $asset->assetCategory->category_name ?? 'N/A'
                ];
            });

        return response()->json($assets);
    }

    public function store(Request $request)
{
    \Log::info('=== Asset Transaction Store Request ===', $request->all());

    // 🔹 For return transactions, get employee_id from latest assignment if not provided
    if ($request->transaction_type === 'return') {
        // First, try to use employee_id_return if employee_id is not set
        if (!$request->employee_id && $request->employee_id_return) {
            $request->merge(['employee_id' => $request->employee_id_return]);
            \Log::info('Using employee_id_return: ' . $request->employee_id_return);
        }
        
        // If still no employee_id, try to get it from the asset's latest assignment
        // We need to load the asset BEFORE validation to get employee_id
        if (!$request->employee_id && $request->asset_id) {
            try {
                $asset = Asset::with('latestTransaction')->find($request->asset_id);
                if ($asset && $asset->latestTransaction && $asset->latestTransaction->employee_id) {
                    $request->merge(['employee_id' => $asset->latestTransaction->employee_id]);
                    \Log::info('Using employee_id from latest transaction: ' . $asset->latestTransaction->employee_id);
                } elseif ($asset && $asset->latestTransaction) {
                    // Try to find from any previous assign transaction
                    $previousAssign = AssetTransaction::where('asset_id', $asset->id)
                        ->where('transaction_type', 'assign')
                        ->whereNotNull('employee_id')
                        ->latest()
                        ->first();
                    if ($previousAssign) {
                        $request->merge(['employee_id' => $previousAssign->employee_id]);
                        \Log::info('Using employee_id from previous assign transaction: ' . $previousAssign->employee_id);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error getting employee_id from asset: ' . $e->getMessage());
            }
        }
    }

    // 🔹 Validation rules
    $rules = [
        'asset_category_id' => 'required|exists:asset_categories,id',
        'asset_id' => 'required|exists:assets,id',
        'transaction_type' => 'required|in:assign,return',
        'employee_id' => 'nullable|exists:employees,id',
        'location_id' => 'nullable|exists:locations,id',
        'project_name' => 'nullable|string',
        'issue_date' => 'nullable|date',
        'return_date' => 'nullable|date',
        'assign_image' => 'nullable|image|max:5120',
        'return_image' => 'nullable|image|max:5120',
    ];

    if ($request->transaction_type === 'assign') {
        $rules['issue_date'] = 'required|date';
    }

    if ($request->transaction_type === 'return') {
        $rules['return_date'] = 'required|date';
        // Make employee_id required, but we've already tried to populate it above
        $rules['employee_id'] = 'required|exists:employees,id';
    }

    $request->validate($rules);

    $asset   = Asset::with('assetCategory')->findOrFail($request->asset_id);
    $latest  = $asset->latestTransaction;
    $category = $asset->assetCategory;

    // 🔹 Business rules
    if ($request->transaction_type === 'assign' && !in_array($asset->status, ['available', 'under_maintenance'])) {
        throw ValidationException::withMessages([
            'asset_id' => 'Asset is not available for assignment.',
        ]);
    }

    if ($request->transaction_type === 'return' && $asset->status !== 'assigned') {
        throw ValidationException::withMessages([
            'asset_id' => 'Only assigned assets can be returned.',
        ]);
    }

    // 🔹 Resolve assignment data
    $data = $this->resolveAssignment($asset, $latest, $request, $category);
    $status = $this->getStatusForTransaction($request->transaction_type);

    // 🔹 Image uploads
    $imageData = $this->handleImageUploads($request);

    try {
        // 🔹 Create transaction
        $transactionData = array_merge([
            'asset_id' => $asset->id,
            'transaction_type' => $request->transaction_type,
            'status' => $status,
            'issue_date' => $request->issue_date,
            'return_date' => $request->return_date,
            'location_id' => $request->location_id,
            'project_name' => $request->project_name,
        ], $data, $imageData);
        
        \Log::info('Creating transaction with data:', $transactionData);
        
        $transaction = AssetTransaction::create($transactionData);
        
        \Log::info('Transaction created successfully. ID: ' . $transaction->id);

        // 🔹 Update asset status
        $asset->update([
            'status' => $status,
        ]);
        
        \Log::info('Asset status updated to: ' . $status);

        // 🔹 Send email
        try {
            $this->sendAssetEmail($transaction);
            \Log::info('Email sent successfully for transaction: ' . $transaction->id);
        } catch (\Exception $emailError) {
            \Log::error('Error sending email: ' . $emailError->getMessage());
            // Don't fail the transaction if email fails
        }

        return redirect()
            ->route('asset-transactions.index')
            ->with('success',
                $request->transaction_type === 'assign'
                    ? 'Asset assigned successfully!'
                    : 'Asset returned successfully!'
            );
    } catch (\Exception $e) {
        \Log::error('Error creating transaction: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        
        return redirect()
            ->back()
            ->withInput()
            ->withErrors(['error' => 'An error occurred while processing the transaction: ' . $e->getMessage()]);
    }
}


    public function update(Request $request, $id)
    {
        $transaction = AssetTransaction::findOrFail($id);

        $request->validate([
            'asset_category_id' => 'required|exists:asset_categories,id',
            'transaction_type' => 'required|in:assign,return,system_maintenance',
            'asset_id' => 'required|exists:assets,id',
            'employee_id' => 'nullable|exists:employees,id',
            'location_id' => 'nullable|exists:locations,id',
            'project_name' => 'nullable|string',
            'issue_date' => 'nullable|date',
            'return_date' => 'nullable|date',
            'receive_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'assign_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'return_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'maintenance_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $asset = Asset::with('assetCategory')->findOrFail($request->asset_id);
        $category = $asset->assetCategory;
        $latest = $asset->latestTransaction;

        // Validate assignment can only be done when asset is available or under maintenance (reassignment after maintenance)
        if (
            $request->transaction_type === 'assign' &&
            !in_array($asset->status, ['available', 'under_maintenance'])
        ) {
            throw ValidationException::withMessages([
                'asset_id' => 'Asset is not available for assignment. Current status: ' . ucfirst($asset->status ?? 'unknown'),
            ]);
        }

        $data = $this->resolveAssignment($asset, $latest, $request, $category);
        $status = $this->getStatusForTransaction($request->transaction_type, $latest);

        // Handle image uploads
        $imageData = $this->handleImageUploads($request, $transaction);

        $transaction->update(array_merge([
            'asset_id' => $asset->id,
            'transaction_type' => $request->transaction_type,
            'location_id' => $request->location_id,
            'project_name' => $request->project_name,
            'issue_date' => $request->issue_date,
            'return_date' => $request->return_date,
            'receive_date' => $request->receive_date,
            'delivery_date' => $request->delivery_date,
            'status' => $status,
        ], $data, $imageData));

        // Update asset status
        $finalStatus = $status;
        if ($request->transaction_type === 'assign' && $latest && $latest->transaction_type === 'system_maintenance') {
            // Assigning from maintenance - restore to previous employee (status already 'assigned')
            $finalStatus = 'assigned';
        } elseif ($request->transaction_type === 'return') {
            // Always set to 'available' when returning (history is tracked in transactions table)
            $finalStatus = 'available';
        }
        
        $asset->status = $finalStatus;
        $asset->save();

        // Send email for all transaction types
        $this->sendAssetEmail($transaction);

        return redirect()->route('asset-transactions.index')->with('success', 'Transaction updated successfully!');
    }

    private function getStatusForTransaction($type)
{
    return match ($type) {
        'assign' => 'assigned',
        'return' => 'available',
        default => 'available',
    };
}

    
    private function resolveAssignment($asset, $latest, $request, $category)
{
    if ($request->transaction_type === 'assign') {
        return [
            'assigned_to_type' => 'employee',
            'employee_id' => $request->employee_id,
            'project_name' => null,
        ];
    }

    if ($request->transaction_type === 'return') {
        // 🔥 KEEP employee_id for history + email
        // If employee_id is not set, try to get it from latest transaction
        $employeeId = $request->employee_id;
        if (!$employeeId && $latest && $latest->employee_id) {
            $employeeId = $latest->employee_id;
            \Log::info('Using employee_id from latest transaction in resolveAssignment: ' . $employeeId);
        }
        
        return [
            'assigned_to_type' => 'employee',
            'employee_id' => $employeeId,
            'project_name' => null,
        ];
    }

    return [];
}



private function sendAssetEmail($transaction)
{
    // Send email for ALL transaction types (assign, return, maintenance)
    $employee = null;
    
    // Reload transaction with relationships
    $transaction = AssetTransaction::with(['asset.assetCategory', 'employee'])->find($transaction->id);
    
    if (!$transaction) {
        \Log::error('Transaction not found for email sending');
        return;
    }
    
    \Log::info('=== Email Sending Debug ===');
    \Log::info('Transaction ID: ' . $transaction->id);
    \Log::info('Transaction Type: ' . $transaction->transaction_type);
    \Log::info('Asset ID: ' . $transaction->asset_id);
    \Log::info('Employee ID in transaction: ' . ($transaction->employee_id ?? 'null'));
    
    // Try to get employee from current transaction
    if ($transaction->employee_id) {
        $employee = Employee::find($transaction->employee_id);
        if ($employee) {
            \Log::info('Found employee from transaction: ' . $employee->name . ' (Email: ' . ($employee->email ?? 'NO EMAIL') . ')');
        }
    }
    
    // For maintenance or return, get employee from previous assignment if not found
    if (!$employee && in_array($transaction->transaction_type, ['system_maintenance', 'return'])) {
        $latestAssign = AssetTransaction::where('asset_id', $transaction->asset_id)
            ->where('transaction_type', 'assign')
            ->whereNotNull('employee_id')
            ->where('id', '!=', $transaction->id) // Exclude current transaction
            ->latest()
            ->first();
            
        if ($latestAssign) {
            $employee = Employee::find($latestAssign->employee_id);
            if ($employee) {
                \Log::info('Found employee from previous assignment: ' . $employee->name . ' (Email: ' . ($employee->email ?? 'NO EMAIL') . ')');
            }
        } else {
            \Log::warning('No previous assignment found for asset ID: ' . $transaction->asset_id);
        }
    }
    
    // Send email if employee exists and has email
    if ($employee) {
        if (empty($employee->email)) {
            \Log::warning('Employee found but no email address: ' . $employee->name . ' (ID: ' . $employee->id . ')');
            return;
        }
        
        try {
            \Log::info('Attempting to send email to: ' . $employee->email);
            \Log::info('Mail driver: ' . config('mail.default'));
            \Log::info('Mail from: ' . config('mail.from.address'));
            
            Mail::to($employee->email)->send(
                new AssetAssigned(
                    $transaction->asset,
                    $employee,
                    $transaction
                )
            );
            
            \Log::info('✓ SUCCESS: Asset transaction email sent to: ' . $employee->email . ' for transaction: ' . $transaction->transaction_type);
        } catch (\Exception $e) {
            // Log error but don't fail the transaction
            \Log::error('✗ FAILED to send asset transaction email to ' . $employee->email);
            \Log::error('Error message: ' . $e->getMessage());
            \Log::error('Error file: ' . $e->getFile() . ':' . $e->getLine());
        }
    } else {
        \Log::warning('No employee found for transaction. Type: ' . $transaction->transaction_type . ', Asset ID: ' . $transaction->asset_id);
    }
    
    \Log::info('=== End Email Debug ===');
}


    public function destroy($id)
    {
        $transaction = AssetTransaction::findOrFail($id);
        $transaction->delete();
        return redirect()->route('asset-transactions.index')->with('success', 'Transaction deleted.');
    }

    public function getLatestEmployee(Asset $asset)
    {
        $latest = $asset->latestTransaction;
        return response()->json([
            'employee_id' => $latest && $latest->transaction_type === 'assign' ? $latest->employee_id : null
        ]);
    }

    public function getAssetDetails($assetId)
    {
        $asset = Asset::with(['assetCategory', 'latestTransaction.employee'])->findOrFail($assetId);
        
        $latestTransaction = $asset->latestTransaction;
        $status = $asset->status ?? 'available';
        
        // Normalize "returned" status to "available" for UI display
        $displayStatus = ($status === 'returned') ? 'available' : $status;
        
        $data = [
            'asset_id' => $asset->asset_id,
            'serial_number' => $asset->serial_number,
            'category_name' => $asset->assetCategory->category_name ?? 'N/A',
            'category_id' => $asset->asset_category_id,
            'status' => $displayStatus, // Use normalized status for UI
            'original_status' => $status, // Keep original for logic
            'current_employee_id' => null,
            'current_employee_name' => null,
            'current_project_name' => null,
            'current_location_id' => null,
            'available_transactions' => []
        ];

        // Get current assignment details
        if ($latestTransaction) {
            $data['current_employee_id'] = $latestTransaction->employee_id;
            $data['current_employee_name'] = $latestTransaction->employee->name ?? null;
            $data['current_employee_email'] = $latestTransaction->employee->email ?? null;
            $data['current_employee_entity'] = $latestTransaction->employee->entity_name ?? null;
            $data['current_project_name'] = $latestTransaction->project_name;
            $data['current_location_id'] = $latestTransaction->location_id;
        }

        // Determine available transaction types based on original status
        if ($status === 'available' || $status === 'returned') {
            // When available/returned: can only assign to new employee
            $data['available_transactions'] = ['assign'];
        } elseif ($status === 'assigned') {
            // When assigned: can only return (use separate maintenance form for maintenance)
            $data['available_transactions'] = ['return'];
        } elseif ($status === 'under_maintenance') {
            // When under maintenance: can only assign (return from maintenance to same employee)
            $data['available_transactions'] = ['assign'];
            // For maintenance, we need to find the employee before maintenance
            $beforeMaintenance = AssetTransaction::with('employee')
                ->where('asset_id', $asset->id)
                ->where('transaction_type', 'assign')
                ->where('id', '<', $latestTransaction->id)
                ->latest()
                ->first();
            if ($beforeMaintenance) {
                $data['current_employee_id'] = $beforeMaintenance->employee_id;
                $data['current_employee_name'] = $beforeMaintenance->employee->name ?? null;
                $data['current_employee_email'] = $beforeMaintenance->employee->email ?? null;
                $data['current_employee_entity'] = $beforeMaintenance->employee->entity_name ?? null;
            }
        }

        return response()->json($data);
    }

    /**
     * Handle image uploads for different transaction types
     */
    private function handleImageUploads(Request $request, $transaction = null)
    {
        $imageData = [];

        // Handle assign image
        if ($request->hasFile('assign_image') && $request->transaction_type === 'assign') {
            $imageData['assign_image'] = $this->uploadImage($request->file('assign_image'), 'assign', $transaction);
        }

        // Handle return image
        if ($request->hasFile('return_image') && $request->transaction_type === 'return') {
            $imageData['return_image'] = $this->uploadImage($request->file('return_image'), 'return', $transaction);
        }

        // Handle maintenance image
        if ($request->hasFile('maintenance_image') && $request->transaction_type === 'system_maintenance') {
            $imageData['maintenance_image'] = $this->uploadImage($request->file('maintenance_image'), 'maintenance', $transaction);
        }

        return $imageData;
    }

    /**
     * Upload and store image file
     */
    private function uploadImage($file, $type, $transaction = null)
    {
        // Delete old image if updating
        if ($transaction) {
            $oldImageField = $type . '_image';
            if ($transaction->$oldImageField && \Storage::disk('public')->exists($transaction->$oldImageField)) {
                \Storage::disk('public')->delete($transaction->$oldImageField);
            }
        }

        // Generate unique filename
        $filename = 'transaction_' . $type . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        
        // Store in storage/app/public/transaction_images
        $path = $file->storeAs('transaction_images', $filename, 'public');
        
        return $path;
    }
}
