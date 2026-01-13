<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

abstract class Controller
{
    /**
     * Check if required database tables exist
     * Returns true if tables exist, false otherwise
     */
    protected function checkDatabaseTables(array $tables = []): bool
    {
        try {
            // Check if migrations table exists (indicates database is set up)
            if (!Schema::hasTable('migrations')) {
                return false;
            }

            // Check specific tables if provided
            foreach ($tables as $table) {
                if (!Schema::hasTable($table)) {
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Database table check error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Handle database errors and redirect with helpful message
     */
    protected function handleDatabaseError(\Exception $e, string $redirectRoute = 'login')
    {
        Log::error('Database error: ' . $e->getMessage());
        
        if (str_contains($e->getMessage(), 'no such table') || 
            str_contains($e->getMessage(), 'does not exist') ||
            str_contains($e->getMessage(), 'Base table or view')) {
            return redirect()->route($redirectRoute)
                ->withErrors(['error' => 'Database tables not found. Please run migrations: php artisan migrate --force']);
        }
        
        return redirect()->route($redirectRoute)
            ->withErrors(['error' => 'An error occurred. Please contact administrator.']);
    }
}
