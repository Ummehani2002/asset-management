<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class NormalizeEmployeeDesignation extends Command
{
    protected $signature = 'employees:normalize-designation
                            {--dry-run : Show what would be updated without changing the database}';
    protected $description = 'Update "Project Engineer" and variants (e.g. Project Engineer (Civil)) to "Assistant Project Manager"';

    public function handle()
    {
        if (!Schema::hasTable('employees') || !Schema::hasColumn('employees', 'designation')) {
            $this->warn('Employees table or designation column not found. Nothing to do.');
            return 0;
        }

        $query = Employee::whereNotNull('designation')
            ->where('designation', '!=', '')
            ->whereRaw('LOWER(TRIM(designation)) LIKE ?', ['project engineer%']);

        $count = $query->count();
        if ($count === 0) {
            $this->info('No employees found with "Project Engineer" (or variant) designation.');
            return 0;
        }

        if ($this->option('dry-run')) {
            $this->info("[Dry run] Would update {$count} employee(s) to designation: Assistant Project Manager");
            $query->get()->each(function ($emp) {
                $this->line("  - {$emp->employee_id} | {$emp->name} | {$emp->designation}");
            });
            return 0;
        }

        $updated = $query->update(['designation' => 'Assistant Project Manager']);
        $this->info("Updated {$updated} employee(s) to designation: Assistant Project Manager.");
        return 0;
    }
}
