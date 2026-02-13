<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckInternetServiceDatabase extends Command
{
    protected $signature = 'internet-service:check';
    protected $description = 'Check database tables and columns required for Internet Service (run in production to diagnose errors)';

    /** Columns that InternetService model expects on internet_services table */
    protected $requiredColumns = [
        'project_id',
        'project_name',
        'entity',
        'service_type',
        'bandwidth',
        'transaction_type',
        'account_number',
        'service_start_date',
        'service_end_date',
        'person_in_charge_id',
        'person_in_charge',
        'contact_details',
        'project_manager_id',
        'project_manager',
        'pm_contact_number',
        'document_controller_id',
        'document_controller',
        'document_controller_number',
        'mrc',
        'cost',
        'pr_number',
        'po_number',
        'status',
    ];

    public function handle(): int
    {
        $this->info('Checking database for Internet Service...');
        $ok = true;

        // 1. Connection
        try {
            DB::connection()->getPdo();
            $this->line('  <info>✓</info> Database connection OK');
        } catch (\Throwable $e) {
            $this->error('  ✗ Database connection failed: ' . $e->getMessage());
            $this->line('');
            $this->warn('Fix: Check DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD in .env or Laravel Cloud environment.');
            return 1;
        }

        // 2. Required tables
        $tables = ['internet_services', 'projects', 'employees'];
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $this->line("  <info>✓</info> Table <comment>{$table}</comment> exists");
            } else {
                $this->error("  ✗ Table <comment>{$table}</comment> is missing");
                $ok = false;
            }
        }

        if (!Schema::hasTable('internet_services')) {
            $this->line('');
            $this->warn('Fix: Run migrations: php artisan migrate --force');
            return 1;
        }

        // 3. Required columns on internet_services
        $missing = [];
        foreach ($this->requiredColumns as $col) {
            if (!Schema::hasColumn('internet_services', $col)) {
                $missing[] = $col;
            }
        }
        if (empty($missing)) {
            $this->line('  <info>✓</info> Table <comment>internet_services</comment> has all required columns');
        } else {
            $this->error('  ✗ Missing columns: ' . implode(', ', $missing));
            $ok = false;
            $this->line('');
            $this->warn('Fix: Run migrations: php artisan migrate --force');
        }

        $this->line('');
        if ($ok) {
            $this->info('All checks passed. Internet Service form should work.');
            return 0;
        }
        $this->warn('Some checks failed. Run: php artisan migrate --force');
        return 1;
    }
}
