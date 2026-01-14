<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class DatabaseCheckController extends Controller
{
    public function check()
    {
        $results = [
            'timestamp' => now()->toDateTimeString(),
            'checks' => []
        ];

        try {
            // Check 1: Database Connection
            try {
                $pdo = DB::connection()->getPdo();
                $dbName = DB::connection()->getDatabaseName();
                $results['checks']['connection'] = [
                    'status' => 'SUCCESS',
                    'database_name' => $dbName,
                    'message' => 'Connected successfully'
                ];
            } catch (\Exception $e) {
                $results['checks']['connection'] = [
                    'status' => 'FAILED',
                    'error' => $e->getMessage()
                ];
                return response()->json($results, 500);
            }

            // Check 2: List All Tables
            try {
                $tables = DB::select('SHOW TABLES');
                $tableList = array_map(function($table) {
                    return array_values((array)$table)[0];
                }, $tables);
                $results['checks']['tables'] = [
                    'status' => 'SUCCESS',
                    'count' => count($tableList),
                    'tables' => $tableList
                ];
            } catch (\Exception $e) {
                $results['checks']['tables'] = [
                    'status' => 'FAILED',
                    'error' => $e->getMessage()
                ];
            }

            // Check 3: Check Specific Tables
            $requiredTables = [
                'employees',
                'locations',
                'asset_categories',
                'brands',
                'assets',
                'projects',
                'sessions',
                'users',
                'migrations'
            ];

            $tableStatus = [];
            foreach ($requiredTables as $table) {
                try {
                    $exists = Schema::hasTable($table);
                    $tableStatus[$table] = $exists ? 'EXISTS' : 'MISSING';
                    
                    if ($exists) {
                        try {
                            $count = DB::table($table)->count();
                            $tableStatus[$table] .= " (Count: $count)";
                        } catch (\Exception $e) {
                            $tableStatus[$table] .= " (Error counting: " . $e->getMessage() . ")";
                        }
                    }
                } catch (\Exception $e) {
                    $tableStatus[$table] = 'ERROR: ' . $e->getMessage();
                }
            }

            $results['checks']['required_tables'] = $tableStatus;

            // Check 4: Migrations Table
            try {
                $migrations = DB::table('migrations')->count();
                $results['checks']['migrations'] = [
                    'status' => 'SUCCESS',
                    'count' => $migrations,
                    'message' => "$migrations migrations recorded"
                ];
            } catch (\Exception $e) {
                $results['checks']['migrations'] = [
                    'status' => 'FAILED',
                    'error' => $e->getMessage()
                ];
            }

            // Check 5: Environment Variables (safe - no sensitive data)
            $results['checks']['environment'] = [
                'db_connection' => config('database.default'),
                'db_database' => config('database.connections.mysql.database'),
                'db_host' => config('database.connections.mysql.host'),
                'db_port' => config('database.connections.mysql.port'),
                'session_driver' => config('session.driver'),
            ];

        } catch (\Exception $e) {
            $results['error'] = $e->getMessage();
            $results['trace'] = $e->getTraceAsString();
        }

        // Return as JSON for easy reading
        return response()->json($results, 200, [], JSON_PRETTY_PRINT);
    }
}
