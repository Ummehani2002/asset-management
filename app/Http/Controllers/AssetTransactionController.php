<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AssetTransaction;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\Entity;
use App\Models\Location;
use App\Models\MaintenanceAssignment;
use App\Models\MaintenanceApprovalRequest;
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
    /** Categories that show "Project Name" in asset transaction; others show "Employee Name". */
    public static function getCategoriesUsingProjectName(): array
    {
        return array_map('strtolower', config('asset_categories.project_name_categories', []));
    }

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
                    // Show only the latest assign transaction for each assigned asset
                    $query->whereHas('asset', function($q) {
                        $q->where('status', 'assigned');
                    });
                    $query->where('transaction_type', 'assign');
                    
                    // Get only the latest assign transaction for each assigned asset
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
                    break;
                case 'maintenance':
                    // Show only the latest maintenance transaction for each asset under maintenance
                    $query->whereHas('asset', function($q) {
                        $q->where('status', 'under_maintenance');
                    });
                    $query->where('transaction_type', 'system_maintenance');
                    
                    // Get only the latest maintenance transaction for each under-maintenance asset
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
                    break;
                case 'return':
                    // Show only the latest return transaction for each asset
                    $query->where('transaction_type', 'return');
                    
                    // Get only the latest return transaction for each asset
                    $latestReturnIds = DB::table('asset_transactions as at1')
                        ->select('at1.id')
                        ->where('at1.transaction_type', 'return')
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
                    break;
                case 'available':
                    // Show only the latest return transaction for each available asset
                    $query->whereHas('asset', function($q) {
                        $q->where('status', 'available');
                    });
                    $query->where('transaction_type', 'return');
                    
                    // Get only the latest return transaction for each available asset
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
                'Status', 'Employee/Project', 'Entity', 'Location', 'Issue Date', 'Return Date', 
                'Receive Date', 'Delivery Date', 'Created At'
            ]);

            // Data
            foreach ($transactions as $index => $t) {
                $assignedTo = $t->employee->name ?? $t->project_name ?? 'N/A';
                $status = $t->asset->status ?? 'N/A';
                $entityDisplay = trim(optional($t->location)->location_entity ?? $t->employee->entity_name ?? '') ?: 'N/A';
                if ($entityDisplay !== 'N/A') {
                    $entityDisplay = ucwords($entityDisplay);
                }
                fputcsv($file, [
                    $index + 1,
                    $t->id,
                    $t->asset->asset_id ?? 'N/A',
                    $t->asset->serial_number ?? 'N/A',
                    $t->asset->assetCategory->category_name ?? 'N/A',
                    ucfirst(str_replace('_', ' ', $t->transaction_type)),
                    $status,
                    $assignedTo,
                    $entityDisplay,
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
                $entities = collect([]);
                $entitiesData = [];
                $categoriesUseProjectName = self::getCategoriesUsingProjectName();
                return view('asset_transactions.create', compact('categories', 'assets', 'employees', 'locations', 'projects', 'entities', 'entitiesData', 'categoriesUseProjectName'))
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
                $entities = collect([]);
                $entitiesData = [];
                $categoriesUseProjectName = self::getCategoriesUsingProjectName();
                return view('asset_transactions.create', compact('categories', 'assets', 'employees', 'locations', 'projects', 'entities', 'entitiesData', 'categoriesUseProjectName'))
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

            $entities = collect([]);
            $entitiesData = [];
            if (Schema::hasTable('entities')) {
                try {
                    $entities = Entity::with('assetManager')->orderBy('name')->get()->map(function ($e) {
                        $am = $e->assetManager;
                        $e->asset_manager_name = $am ? ($am->name ?? $am->entity_name ?? 'N/A') : null;
                        $e->asset_manager_employee_id = $am?->employee_id ?? null;
                        return $e;
                    });
                    $entitiesData = $entities->map(function ($e) {
                        return [
                            'id' => $e->id,
                            'name' => strtolower($e->name ?? ''),
                            'display_name' => ucwords($e->name ?? ''),
                            'asset_manager_name' => $e->asset_manager_name ?? null,
                            'asset_manager_employee_id' => $e->asset_manager_employee_id ?? null,
                        ];
                    })->values()->toArray();
                } catch (\Exception $e) {
                    Log::warning('Error loading entities: ' . $e->getMessage());
                }
            }
            $hasAllTables = $hasAssetCategories && $hasAssets && $hasEmployees && $hasLocations && $hasProjects;
            $categoriesUseProjectName = self::getCategoriesUsingProjectName();
            return view('asset_transactions.create', compact('categories', 'assets', 'employees', 'locations', 'projects', 'entities', 'entitiesData', 'categoriesUseProjectName'))
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
            $entities = collect([]);
            $entitiesData = [];
            $categoriesUseProjectName = self::getCategoriesUsingProjectName();
            return view('asset_transactions.create', compact('categories', 'assets', 'employees', 'locations', 'projects', 'entities', 'entitiesData', 'categoriesUseProjectName'))
                ->with('error', 'An error occurred. Please check Laravel Cloud logs for details.');
        }
    }

    public function maintenance()
    {
        $categories = \App\Models\AssetCategory::all();
        $entities = Entity::with('assetManager')->orderBy('name')->get()->map(function ($e) {
            $am = $e->assetManager;
            $e->asset_manager_name = $am ? ($am->name ?? $am->entity_name ?? 'N/A') : null;
            $e->asset_manager_employee_id = $am?->employee_id ?? null;
            $e->asset_manager_id = $am?->id ?? null;
            return $e;
        });
        $assetManagerIds = Entity::whereNotNull('asset_manager_id')->pluck('asset_manager_id')->unique();
        $assetManagers = Employee::whereIn('id', $assetManagerIds)->orderBy('name')->get(['id', 'name', 'entity_name', 'employee_id']);
        $assetManagers = $assetManagers->map(function ($am) {
            $entities = Entity::where('asset_manager_id', $am->id)->pluck('name')->map(fn($n) => ucwords($n))->join(', ');
            $am->managed_entities = $entities ?: ($am->entity_name ? ucwords($am->entity_name) : '-');
            return $am;
        });
        $pendingApprovals = collect([]);
        $allPendingAssignments = collect([]);
        if (auth()->user()?->employee_id) {
            $pendingApprovals = MaintenanceAssignment::with(['asset.assetCategory', 'assetTransaction.employee', 'assetTransaction.location', 'assignedBy', 'assignedTo'])
                ->where('assigned_to_employee_id', auth()->user()->employee_id)
                ->where('status', 'pending')
                ->orderByDesc('created_at')
                ->get();
        }
        // All pending assignments - visible to everyone for transparency
        $allPendingAssignments = MaintenanceAssignment::with(['asset.assetCategory', 'assetTransaction.employee', 'assetTransaction.location', 'assignedBy', 'assignedTo'])
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();
        $enrichAssignment = function ($pa) {
            $txn = $pa->assetTransaction;
            $entityName = null;
            if ($txn && $txn->location_id) {
                $loc = $txn->relationLoaded('location') ? $txn->location : Location::find($txn->location_id);
                if ($loc && !empty($loc->location_entity)) {
                    $entityName = ucwords(trim($loc->location_entity));
                }
            }
            if (!$entityName && $txn && $txn->employee_id) {
                $entityName = $txn->employee?->entity_name ? ucwords($txn->employee->entity_name) : null;
            }
            $pa->asset_entity = $entityName ?? '-';
            $pa->assigned_by_entities = Entity::where('asset_manager_id', $pa->assigned_by_employee_id)->pluck('name')->map(fn($n) => ucwords($n))->join(', ') ?: '-';
            $pa->assigned_to_name = $pa->assignedTo?->name ?? $pa->assignedTo?->entity_name ?? 'N/A';
        };
        $pendingApprovals->each($enrichAssignment);
        $allPendingAssignments->each($enrichAssignment);

        // Pending maintenance approval requests (someone requested approval; current user is the AM who must approve)
        $pendingMaintenanceRequests = collect([]);
        if (auth()->user()?->employee_id) {
            $pendingMaintenanceRequests = MaintenanceApprovalRequest::with(['asset.assetCategory', 'requestedByUser'])
                ->where('assigned_to_employee_id', auth()->user()->employee_id)
                ->where('status', 'pending')
                ->orderByDesc('created_at')
                ->get();
        }

        // Only asset managers (assigned in Entity Master) can delegate to another AM when busy
        $currentUserIsAssetManager = auth()->user()?->employee_id && $assetManagers->contains('id', auth()->user()->employee_id);
        $canDelegateToOthers = $currentUserIsAssetManager && $assetManagers->where('id', '!=', auth()->user()->employee_id)->isNotEmpty();
        return view('asset_transactions.maintenance', compact('categories', 'entities', 'assetManagers', 'pendingApprovals', 'allPendingAssignments', 'pendingMaintenanceRequests', 'canDelegateToOthers'));
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

        // Only the asset manager for this asset (or user with an approved request) can save maintenance
        $currentUserEmployeeId = auth()->user()?->employee_id;
        $assetManagerId = null;
        $entityName = null;
        if ($latest && $latest->location_id) {
            $location = Location::find($latest->location_id);
            $entityName = $location && !empty(trim($location->location_entity ?? '')) ? trim($location->location_entity) : null;
        }
        if (!$entityName && $latest && $latest->employee_id) {
            $entityName = $latest->employee?->entity_name ?? null;
        }
        if ($entityName) {
            $entity = Entity::whereRaw('LOWER(name) = ?', [strtolower($entityName)])->with('assetManager')->first()
                ?? Entity::whereRaw('LOWER(name) LIKE ?', [strtolower($entityName) . '%'])->with('assetManager')->orderBy('name')->first();
            $assetManagerId = $entity?->asset_manager_id;
        }
        $isAssetManager = $currentUserEmployeeId && $assetManagerId && (int) $assetManagerId === (int) $currentUserEmployeeId;
        // Requester can save after the entity's AM approved their request
        $hasApprovedRequest = auth()->id() && MaintenanceApprovalRequest::where('asset_id', $asset->id)
            ->where('requested_by_user_id', auth()->id())
            ->where('status', 'approved')
            ->exists();
        if ($assetManagerId && !$isAssetManager && !$hasApprovedRequest) {
            throw ValidationException::withMessages([
                'asset_id' => 'You are not the asset manager for this asset. Request approval from the asset manager first, or wait for them to approve your request.',
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

        // Find the assignment before maintenance (for reassign and maintenance actions)
        $employeeId = null;
        $assignedToType = 'employee';
        $projectName = null;
        $locationId = null;
        
        // Need previous assignment for 'reassign', 'maintenance', and 'return' (return: to mark employee inactive)
        if (in_array($request->action_type, ['reassign', 'maintenance', 'return'])) {
            // Use the same logic as getAssetDetails method for consistency
            // Strategy 1: Find assignment before maintenance (exact same query as getAssetDetails)
            $beforeMaintenance = AssetTransaction::with('employee')
                ->where('asset_id', $asset->id)
                ->where('transaction_type', 'assign')
                ->where('id', '<', $latest->id)
                ->latest()
                ->first();
            
            if ($beforeMaintenance && $beforeMaintenance->employee_id) {
                $employeeId = $beforeMaintenance->employee_id;
                $assignedToType = $beforeMaintenance->assigned_to_type ?? 'employee';
                $projectName = $beforeMaintenance->project_name;
                $locationId = $beforeMaintenance->location_id;
            } else {
                // Strategy 2: Use employee from maintenance transaction if available
                if ($latest->employee_id) {
                    $employeeId = $latest->employee_id;
                    $assignedToType = $latest->assigned_to_type ?? 'employee';
                    $projectName = $latest->project_name;
                    $locationId = $latest->location_id;
                } else {
                    // Strategy 3: Search for ANY previous assignment transaction (fallback)
                    $anyPreviousAssignment = AssetTransaction::where('asset_id', $asset->id)
                        ->where('transaction_type', 'assign')
                        ->whereNotNull('employee_id')
                        ->latest()
                        ->first();
                    
                    if ($anyPreviousAssignment && $anyPreviousAssignment->employee_id) {
                        $employeeId = $anyPreviousAssignment->employee_id;
                        $assignedToType = $anyPreviousAssignment->assigned_to_type ?? 'employee';
                        $projectName = $anyPreviousAssignment->project_name;
                        $locationId = $anyPreviousAssignment->location_id;
                    } else {
                        // Strategy 4: Search for ANY transaction with employee_id (last resort)
                        $anyTransactionWithEmployee = AssetTransaction::where('asset_id', $asset->id)
                            ->whereNotNull('employee_id')
                            ->latest()
                            ->first();
                        
                        if ($anyTransactionWithEmployee && $anyTransactionWithEmployee->employee_id) {
                            $employeeId = $anyTransactionWithEmployee->employee_id;
                            $assignedToType = $anyTransactionWithEmployee->assigned_to_type ?? 'employee';
                            $projectName = $anyTransactionWithEmployee->project_name;
                            $locationId = $anyTransactionWithEmployee->location_id;
                        }
                    }
                }
            }
        }

        $imageData = [];
        $transaction = null;
        $successMessage = '';

        // Handle different action types
        if ($request->action_type === 'reassign') {
            // Reassign to same employee (or make available if no employee found)
            if ($request->hasFile('reassign_image')) {
                $imageData['assign_image'] = $this->uploadImage($request->file('reassign_image'), 'assign');
            }

            // Check if employee was found - must have valid employee_id for reassign
            if (empty($employeeId)) {
                // No employee found - make asset available instead of assigned
                $transaction = AssetTransaction::create(array_merge([
                    'asset_id' => $asset->id,
                    'transaction_type' => 'return',
                    'status' => 'available',
                    'return_date' => $request->reassign_date,
                    'assigned_to_type' => null,
                    'employee_id' => null,
                    'project_name' => null,
                    'location_id' => null,
                    'maintenance_notes' => ($request->reassign_notes ?? '') . ' (Reassigned from maintenance - no previous employee found)',
                ], $imageData));

                $asset->update(['status' => 'available']);
                $successMessage = 'Asset returned from maintenance successfully! Asset is now available for assignment. (No previous employee information found)';
            } else {
                // Employee found - verify employee still exists
                $employee = Employee::find($employeeId);
                if (!$employee) {
                    // Employee no longer exists - make asset available
                    $transaction = AssetTransaction::create(array_merge([
                        'asset_id' => $asset->id,
                        'transaction_type' => 'return',
                        'status' => 'available',
                        'return_date' => $request->reassign_date,
                        'assigned_to_type' => null,
                        'employee_id' => null,
                        'project_name' => null,
                        'location_id' => null,
                        'maintenance_notes' => ($request->reassign_notes ?? '') . ' (Reassigned from maintenance - employee no longer exists)',
                    ], $imageData));

                    $asset->update(['status' => 'available']);
                    $successMessage = 'Asset returned from maintenance successfully! Asset is now available for assignment. (Previous employee no longer exists)';
                } else {
                    // Employee exists - reassign to same employee
                    $transaction = AssetTransaction::create(array_merge([
                        'asset_id' => $asset->id,
                        'transaction_type' => 'assign',
                        'status' => 'assigned',
                        'issue_date' => $request->reassign_date,
                        'assigned_to_type' => $assignedToType ?? 'employee',
                        'employee_id' => $employeeId,
                        'project_name' => $projectName,
                        'location_id' => $locationId,
                        'maintenance_notes' => $request->reassign_notes,
                    ], $imageData));

                    $asset->update(['status' => 'assigned']);
                    $successMessage = 'Asset reassigned to same employee successfully! Email notification sent. Asset is ready for collection.';
                }
            }

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
                'assigned_to_type' => $assignedToType,
                'employee_id' => $employeeId,
                'project_name' => $projectName,
                'location_id' => $locationId,
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

            // Mark employee inactive if they have no other assigned assets
            if ($employeeId) {
                $employee = Employee::find($employeeId);
                if ($employee) {
                    $assignedCount = Asset::whereHas('latestTransaction', function ($q) use ($employeeId) {
                        $q->where('employee_id', $employeeId)->where('transaction_type', 'assign');
                    })->count();
                    if ($assignedCount === 0) {
                        $employee->update(['is_active' => false]);
                    }
                }
            }
        }

        // Send email to employee (only for reassign and if employee exists)
        if ($transaction && $request->action_type === 'reassign' && $employeeId && $transaction->transaction_type === 'assign') {
            $this->sendAssetEmail($transaction);
        }

        return redirect()->route('asset-transactions.index')
            ->with('success', $successMessage);
    }

    public function maintenanceAssign(Request $request)
    {
        $request->validate([
            'asset_transaction_id' => 'required|exists:asset_transactions,id',
            'asset_id' => 'required|exists:assets,id',
            'assigned_to_employee_id' => 'required|exists:employees,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        if (!$user || !$user->employee_id) {
            return redirect()->back()->with('error', 'You must be linked to an employee (asset manager) to assign maintenance.');
        }

        $transaction = AssetTransaction::findOrFail($request->asset_transaction_id);
        if ($transaction->transaction_type !== 'system_maintenance') {
            return redirect()->back()->with('error', 'Invalid maintenance transaction.');
        }

        $asset = Asset::findOrFail($request->asset_id);
        if ($asset->status !== 'under_maintenance') {
            return redirect()->back()->with('error', 'Asset is not under maintenance.');
        }

        $assignedTo = Employee::findOrFail($request->assigned_to_employee_id);
        if ($assignedTo->id === $user->employee_id) {
            return redirect()->back()->with('error', 'You cannot assign maintenance to yourself.');
        }

        $existing = MaintenanceAssignment::where('asset_transaction_id', $transaction->id)
            ->where('status', 'pending')
            ->first();
        if ($existing) {
            return redirect()->back()->with('error', 'This maintenance already has a pending assignment.');
        }

        MaintenanceAssignment::create([
            'asset_transaction_id' => $transaction->id,
            'asset_id' => $asset->id,
            'assigned_by_employee_id' => $user->employee_id,
            'assigned_to_employee_id' => $assignedTo->id,
            'status' => 'pending',
            'notes' => $request->notes,
        ]);

        return redirect()->route('asset-transactions.maintenance')
            ->with('success', 'Maintenance assigned to ' . ($assignedTo->name ?? $assignedTo->entity_name ?? 'N/A') . '. They must approve before they can process it.');
    }

    public function maintenanceApprove($id)
    {
        $assignment = MaintenanceAssignment::with(['asset', 'assignedBy'])->findOrFail($id);
        $user = auth()->user();
        if (!$user || $user->employee_id != $assignment->assigned_to_employee_id) {
            return redirect()->back()->with('error', 'You are not authorized to approve this assignment.');
        }
        if ($assignment->status !== 'pending') {
            return redirect()->back()->with('error', 'This assignment has already been processed.');
        }

        $assignment->update(['status' => 'approved', 'approved_at' => now()]);

        return redirect()->route('asset-transactions.maintenance')
            ->with('success', 'Assignment approved! You can now process this asset in the "Reassign from Maintenance" tab.');
    }

    public function maintenanceReject($id)
    {
        $assignment = MaintenanceAssignment::findOrFail($id);
        $user = auth()->user();
        if (!$user || $user->employee_id != $assignment->assigned_to_employee_id) {
            return redirect()->back()->with('error', 'You are not authorized to reject this assignment.');
        }
        if ($assignment->status !== 'pending') {
            return redirect()->back()->with('error', 'This assignment has already been processed.');
        }

        $assignment->update(['status' => 'rejected', 'rejected_at' => now()]);

        return redirect()->route('asset-transactions.maintenance')
            ->with('success', 'Assignment rejected.');
    }

    /**
     * Non–asset manager requests approval to send an asset for maintenance. Notifies the asset manager.
     */
    public function requestMaintenanceApproval(Request $request)
    {
        $request->validate(['asset_id' => 'required|exists:assets,id', 'request_notes' => 'nullable|string|max:500']);

        $asset = Asset::with('assetCategory')->findOrFail($request->asset_id);
        if ($asset->status !== 'assigned') {
            return redirect()->back()->with('error', 'Only assigned assets can be sent for maintenance.');
        }

        $latestTransaction = $asset->latestTransaction;
        if (!$latestTransaction) {
            return redirect()->back()->with('error', 'Asset has no assignment. Cannot request maintenance.');
        }

        $location = $latestTransaction->location_id
            ? Location::find($latestTransaction->location_id)
            : null;
        $entityName = null;
        if ($location && !empty(trim($location->location_entity ?? ''))) {
            $entityName = trim($location->location_entity);
        }
        if (!$entityName && $latestTransaction->employee_id) {
            $entityName = $latestTransaction->employee?->entity_name ?? null;
        }
        if (!$entityName) {
            return redirect()->back()->with('error', 'Could not determine entity for this asset. Assign asset manager in Entity Master.');
        }

        $entity = Entity::whereRaw('LOWER(name) = ?', [strtolower($entityName)])->with('assetManager')->first()
            ?? Entity::whereRaw('LOWER(name) LIKE ?', [strtolower($entityName) . '%'])->with('assetManager')->orderBy('name')->first()
            ?? Entity::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($entityName) . '%'])->with('assetManager')->orderBy('name')->first();

        if (!$entity || !$entity->asset_manager_id) {
            return redirect()->back()->with('error', 'No asset manager assigned for this entity. Assign in Asset Manager / Entity Master first.');
        }

        $existing = MaintenanceApprovalRequest::where('asset_id', $asset->id)
            ->where('assigned_to_employee_id', $entity->asset_manager_id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();
        if ($existing) {
            return redirect()->back()->with('error', 'A request for this asset is already pending or approved.');
        }

        MaintenanceApprovalRequest::create([
            'asset_id' => $asset->id,
            'requested_by_user_id' => auth()->id(),
            'assigned_to_employee_id' => $entity->asset_manager_id,
            'status' => 'pending',
            'request_notes' => $request->request_notes,
        ]);

        $amName = $entity->assetManager?->name ?? $entity->assetManager?->entity_name ?? 'Asset Manager';
        return redirect()->route('asset-transactions.maintenance')
            ->with('success', 'Approval request sent to ' . $amName . '. They will be able to approve and then fill maintenance details.');
    }

    /**
     * Asset manager approves a maintenance approval request. After this, they can fill and save maintenance for that asset.
     */
    public function approveMaintenanceRequest($id)
    {
        $req = MaintenanceApprovalRequest::with('asset.assetCategory', 'requestedByUser', 'assignedToEmployee')->findOrFail($id);
        $user = auth()->user();
        if (!$user || $user->employee_id != $req->assigned_to_employee_id) {
            return redirect()->back()->with('error', 'You are not the asset manager for this request.');
        }
        if ($req->status !== 'pending') {
            return redirect()->back()->with('error', 'This request has already been processed.');
        }

        $req->update(['status' => 'approved', 'approved_at' => now()]);

        return redirect()->route('asset-transactions.maintenance')
            ->with('success', 'Request approved. Go to "Send for Maintenance" tab, select asset ' . ($req->asset->serial_number ?? $req->asset->asset_id ?? '') . ', and fill the maintenance details to save.');
    }

    /**
     * Asset manager rejects a maintenance approval request.
     */
    public function rejectMaintenanceRequest($id)
    {
        $req = MaintenanceApprovalRequest::findOrFail($id);
        $user = auth()->user();
        if (!$user || $user->employee_id != $req->assigned_to_employee_id) {
            return redirect()->back()->with('error', 'You are not authorized to reject this request.');
        }
        if ($req->status !== 'pending') {
            return redirect()->back()->with('error', 'This request has already been processed.');
        }

        $req->update(['status' => 'rejected', 'rejected_at' => now()]);

        return redirect()->route('asset-transactions.maintenance')
            ->with('success', 'Request rejected.');
    }

    public function edit($id)
    {
        $transaction = AssetTransaction::with(['asset.assetCategory', 'employee'])->findOrFail($id);
        $categories = \App\Models\AssetCategory::all();
        $assets = Asset::with('assetCategory')->get();
        $employees = Employee::all();
        $locations = Location::all();
        $projects = \App\Models\Project::all();
        $entities = Entity::with('assetManager')->orderBy('name')->get()->map(function ($e) {
            $am = $e->assetManager;
            $e->asset_manager_name = $am ? ($am->name ?? $am->entity_name ?? 'N/A') : null;
            $e->asset_manager_employee_id = $am?->employee_id ?? null;
            return $e;
        });
        $entitiesData = $entities->map(function ($e) {
            return [
                'id' => $e->id,
                'name' => strtolower($e->name ?? ''),
                'display_name' => ucwords($e->name ?? ''),
                'asset_manager_name' => $e->asset_manager_name ?? null,
                'asset_manager_employee_id' => $e->asset_manager_employee_id ?? null,
            ];
        })->values()->toArray();

        $categoriesUseProjectName = self::getCategoriesUsingProjectName();
        return view('asset_transactions.create', compact('transaction', 'categories', 'assets', 'employees', 'locations', 'projects', 'entities', 'entitiesData', 'categoriesUseProjectName'));
    }

    public function getAssetsByCategory(Request $request, $categoryId)
    {
        $query = Asset::with('assetCategory')->where('asset_category_id', $categoryId);
        $search = trim($request->get('q', ''));
        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(function ($q) use ($like) {
                $q->where('serial_number', 'LIKE', $like)
                  ->orWhere('asset_id', 'LIKE', $like);
            });
        }
        $assets = $query->get()
            ->map(function ($asset) {
                $originalStatus = $asset->status ?? 'available';
                $displayStatus = ($originalStatus === 'returned') ? 'available' : $originalStatus;
                return [
                    'id' => $asset->id,
                    'asset_id' => $asset->asset_id,
                    'serial_number' => $asset->serial_number,
                    'status' => $displayStatus,
                    'original_status' => $originalStatus,
                    'category_name' => $asset->assetCategory->category_name ?? 'N/A'
                ];
            });

        return response()->json($assets);
    }

    public function getLocations(Request $request)
    {
        $query = Location::query();
        if ($request->filled('entity')) {
            $query->whereRaw('LOWER(location_entity) = ?', [strtolower(trim($request->entity))]);
        }
        $locations = $query->orderBy('location_name')->get(['id', 'location_name', 'location_entity', 'location_country']);
        return response()->json($locations);
    }

    public function getMaintenanceAssetsByCategory(Request $request, $categoryId)
    {
        $query = Asset::with('assetCategory')
            ->where('asset_category_id', $categoryId)
            ->where('status', 'under_maintenance');
        $search = trim($request->get('q', ''));
        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(function ($q) use ($like) {
                $q->where('serial_number', 'LIKE', $like)
                  ->orWhere('asset_id', 'LIKE', $like);
            });
        }
        $assets = $query->get()
            ->map(function ($asset) {
                $latest = $asset->latestTransaction;
                $txnId = $latest && $latest->transaction_type === 'system_maintenance' ? $latest->id : null;
                return [
                    'id' => $asset->id,
                    'asset_id' => $asset->asset_id,
                    'serial_number' => $asset->serial_number,
                    'transaction_id' => $txnId,
                    'category_name' => $asset->assetCategory->category_name ?? 'N/A',
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
        // When asset was assigned to project (no employee), return does not require employee_id
        $returnAsset = $request->asset_id ? Asset::with('latestTransaction')->find($request->asset_id) : null;
        $latestForReturn = $returnAsset && $returnAsset->latestTransaction ? $returnAsset->latestTransaction : null;
        $wasAssignedToProject = $latestForReturn && ($latestForReturn->assigned_to_type === 'project' || ($latestForReturn->project_name && !$latestForReturn->employee_id));
        $rules['employee_id'] = $wasAssignedToProject ? 'nullable|exists:employees,id' : 'required|exists:employees,id';
    }

    // Assign: require Employee Name or Project Name based on category (config: project_name_categories)
    if ($request->transaction_type === 'assign' && $request->asset_id) {
        $assignAsset = Asset::with('assetCategory')->find($request->asset_id);
        if ($assignAsset && $assignAsset->assetCategory) {
            $catName = strtolower($assignAsset->assetCategory->category_name ?? '');
            $useProject = in_array($catName, self::getCategoriesUsingProjectName());
            if ($useProject) {
                $rules['project_name'] = 'required|string';
                $rules['employee_id'] = 'nullable|exists:employees,id';
            } else {
                $rules['employee_id'] = 'required|exists:employees,id';
                $rules['project_name'] = 'nullable|string';
            }
        }
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

        // 🔹 Update asset status (and location if assets table has location_id)
        $assetUpdate = ['status' => $status];
        if ($request->transaction_type === 'assign' && $request->location_id && \Schema::hasColumn('assets', 'location_id')) {
            $location = Location::find($request->location_id);
            if ($location) {
                $assetUpdate['location_id'] = $location->id;
            }
        }
        $asset->update($assetUpdate);
        
        \Log::info('Asset status updated to: ' . $status);

        // 🔹 Update employee active status: mark inactive when returning last asset, active when assigning
        $employeeId = $transactionData['employee_id'] ?? null;
        if ($employeeId) {
            $employee = Employee::find($employeeId);
            if ($employee) {
                if ($request->transaction_type === 'assign') {
                    $employee->update(['is_active' => true]);
                } elseif ($request->transaction_type === 'return') {
                    $assignedCount = Asset::whereHas('latestTransaction', function ($q) use ($employeeId) {
                        $q->where('employee_id', $employeeId)->where('transaction_type', 'assign');
                    })->count();
                    if ($assignedCount === 0) {
                        $employee->update(['is_active' => false]);
                    }
                }
            }
        }

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
        $projectName = $request->project_name ? trim($request->project_name) : null;
        $employeeId = $request->employee_id ?: null;
        $assignedToType = ($projectName && !$employeeId) ? 'project' : 'employee';
        return [
            'assigned_to_type' => $assignedToType,
            'employee_id' => $employeeId,
            'project_name' => $projectName,
            'location_id' => $request->location_id ?: null,
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
        $asset = Asset::with(['assetCategory', 'latestTransaction.employee', 'latestTransaction.location'])->findOrFail($assetId);
        
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
            'location_name' => null,
            'location_entity' => null,
            'location_country' => null,
            'entity_id' => null,
            'available_transactions' => []
        ];

        // Helper: resolve entity and asset manager from the ASSIGNMENT's location (same as Assign form)
        // For Return/Maintenance: use location from assign transaction, or from asset when transaction has no location_id
        $resolveEntityAndAssetManager = function ($transaction, $asset = null) {
            $entityName = null;
            $location = null;
            if ($transaction && $transaction->location_id) {
                $location = $transaction->relationLoaded('location') ? $transaction->location : Location::find($transaction->location_id);
                if ($location && !empty(trim($location->location_entity ?? ''))) {
                    $entityName = trim($location->location_entity);
                }
            }
            // Fallback: transaction has no location_id - try asset's location (e.g. from backfill or old assigns)
            if (!$location && $asset && !empty($asset->location_id)) {
                $location = Location::find($asset->location_id);
                if ($location && !empty(trim($location->location_entity ?? ''))) {
                    $entityName = trim($location->location_entity);
                }
            }
            // Only fall back to employee when we have NO location (e.g. printer, old data)
            if (!$entityName && $transaction && $transaction->employee_id) {
                $entityName = $transaction->employee?->entity_name ?? null;
            }
            if (!$entityName) {
                return [null, null, null, null, null, null];
            }
            $search = strtolower(trim($entityName));
            $entity = Entity::whereRaw('LOWER(name) = ?', [$search])->with('assetManager')->first();
            if (!$entity) {
                $entity = Entity::whereRaw('LOWER(name) LIKE ?', [$search . '%'])->with('assetManager')->orderBy('name')->first();
            }
            if (!$entity) {
                $entity = Entity::whereRaw('LOWER(name) LIKE ?', ['%' . $search . '%'])->with('assetManager')->orderBy('name')->first();
            }
            if (!$entity) {
                return [ucwords($entityName), null, null, null, null, null];
            }
            $am = $entity->assetManager;
            return [
                ucwords($entity->name),
                $entity->id,
                $am?->id,
                $am ? ($am->name ?? $am->entity_name ?? 'N/A') : null,
                $am?->employee_id ?? null,
                $location
            ];
        };

        // Get current assignment details
        if ($latestTransaction) {
            $data['current_employee_id'] = $latestTransaction->employee_id;
            $data['current_employee_name'] = $latestTransaction->employee?->name ?? null;
            $data['current_employee_email'] = $latestTransaction->employee?->email ?? null;
            $data['current_employee_entity'] = $latestTransaction->employee?->entity_name ?? null;
            $data['current_project_name'] = $latestTransaction->project_name;
            $data['current_location_id'] = $latestTransaction->location_id;

            list($entityDisplay, $entityId, $amId, $amName, $amEmpId, $location) = $resolveEntityAndAssetManager($latestTransaction, $asset);
            $data['asset_manager_id'] = $amId;
            $data['asset_manager_name'] = $amName;
            $data['asset_manager_employee_id'] = $amEmpId ?? null;
            $data['asset_manager_entity'] = $entityDisplay;
            $data['entity_id'] = $entityId;
            if ($location) {
                $data['location_name'] = $location->location_name ?? null;
                $data['location_entity'] = $location->location_entity ?? null;
                $data['location_country'] = $location->location_country ?? null;
            }
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
            // For maintenance, we need to find the employee and location before maintenance
            $beforeMaintenance = AssetTransaction::with(['employee', 'location'])
                ->where('asset_id', $asset->id)
                ->where('transaction_type', 'assign')
                ->where('id', '<', $latestTransaction->id)
                ->latest()
                ->first();
            if ($beforeMaintenance) {
                $data['current_employee_id'] = $beforeMaintenance->employee_id;
                $data['current_employee_name'] = $beforeMaintenance->employee?->name ?? null;
                $data['current_employee_email'] = $beforeMaintenance->employee?->email ?? null;
                $data['current_employee_entity'] = $beforeMaintenance->employee?->entity_name ?? null;
                $data['current_location_id'] = $beforeMaintenance->location_id;
                // Use location entity (linked with location) for asset manager
                list($entityDisplay, $entityId, $amId, $amName, $amEmpId, $location) = $resolveEntityAndAssetManager($beforeMaintenance, $asset);
                $data['asset_manager_id'] = $amId;
                $data['asset_manager_name'] = $amName;
                $data['asset_manager_employee_id'] = $amEmpId ?? null;
                $data['asset_manager_entity'] = $entityDisplay;
                $data['entity_id'] = $entityId;
                if ($location) {
                    $data['location_name'] = $location->location_name ?? null;
                    $data['location_entity'] = $location->location_entity ?? null;
                    $data['location_country'] = $location->location_country ?? null;
                }
            }
        }

        // For System Maintenance: can current user fill maintenance details? (only asset manager or after approval)
        $currentUserEmployeeId = auth()->user()?->employee_id;
        $data['is_current_user_asset_manager'] = false;
        $data['has_approved_request'] = false;
        $data['can_fill_maintenance'] = true;
        if ($status === 'assigned' && $asset) {
            if (!empty($data['asset_manager_id'])) {
                $data['is_current_user_asset_manager'] = $currentUserEmployeeId && (int) $data['asset_manager_id'] === (int) $currentUserEmployeeId;
                // Requester (e.g. second asset manager) can fill only after the entity's AM approves their request
                $data['has_approved_request'] = auth()->id() && MaintenanceApprovalRequest::where('asset_id', $asset->id)
                    ->where('requested_by_user_id', auth()->id())
                    ->where('status', 'approved')
                    ->exists();
                $data['can_fill_maintenance'] = $data['is_current_user_asset_manager'] || $data['has_approved_request'];
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
