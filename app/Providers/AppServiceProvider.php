<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ensure SQLite database file exists if using SQLite
        if (config('database.default') === 'sqlite') {
            try {
                $databasePath = config('database.connections.sqlite.database');
                
                if ($databasePath && !file_exists($databasePath)) {
                    // Create the database directory if it doesn't exist
                    $directory = dirname($databasePath);
                    if (!is_dir($directory)) {
                        mkdir($directory, 0755, true);
                    }
                    
                    // Create an empty SQLite database file
                    touch($databasePath);
                    chmod($databasePath, 0644);
                }
            } catch (\Exception $e) {
                // Silently fail - database will be created when migrations run
                Log::warning('Could not create SQLite database file: ' . $e->getMessage());
            }
        }
    }
}
