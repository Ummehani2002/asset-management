<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearAllData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:clear {--keep-users : Keep users table data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all data from database tables (preserves table structure)';

    /**
     * Tables to exclude from clearing (system tables)
     */
    protected $excludedTables = [
        'migrations',
        'sessions',
        'password_reset_tokens',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->confirm('⚠️  WARNING: This will delete ALL data from all tables. Are you sure?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info('Starting data clearing process...');
        $this->newLine();

        try {
            // Get all tables
            $tables = DB::select('SHOW TABLES');
            $dbName = DB::connection()->getDatabaseName();
            $tableKey = 'Tables_in_' . $dbName;

            $clearedCount = 0;
            $skippedCount = 0;
            $errors = [];

            foreach ($tables as $table) {
                $tableName = $table->$tableKey;

                // Skip excluded tables
                if (in_array($tableName, $this->excludedTables)) {
                    $this->line("⏭️  Skipping system table: {$tableName}");
                    $skippedCount++;
                    continue;
                }

                // Skip users table if --keep-users flag is set
                if ($tableName === 'users' && $this->option('keep-users')) {
                    $this->line("⏭️  Skipping users table (--keep-users flag set)");
                    $skippedCount++;
                    continue;
                }

                try {
                    // Disable foreign key checks temporarily
                    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                    
                    // Truncate table (faster than delete, resets auto-increment)
                    DB::table($tableName)->truncate();
                    
                    // Re-enable foreign key checks
                    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                    
                    $this->info("✅ Cleared: {$tableName}");
                    $clearedCount++;
                } catch (\Exception $e) {
                    $errorMsg = "❌ Error clearing {$tableName}: " . $e->getMessage();
                    $this->error($errorMsg);
                    $errors[] = $errorMsg;
                }
            }

            $this->newLine();
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("✅ Data clearing completed!");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("Tables cleared: {$clearedCount}");
            $this->info("Tables skipped: {$skippedCount}");
            
            if (!empty($errors)) {
                $this->newLine();
                $this->warn("Errors encountered: " . count($errors));
                foreach ($errors as $error) {
                    $this->line("  - {$error}");
                }
            }

            $this->newLine();
            $this->info("💡 Tip: Your team can now start entering actual data!");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Fatal error: " . $e->getMessage());
            return 1;
        }
    }
}
