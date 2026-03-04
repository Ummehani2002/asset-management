<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CopyDataFromOldDb extends Command
{
    protected $signature = 'db:copy-from-old
                            {--force : Skip confirmation}
                            {--chunk=500 : Rows per chunk}';

    protected $description = 'Copy data from old/source DB (mysql_old) to default DB. Set OLD_DB_* in .env.';

    /** Tables to skip (system / not needed or risky to overwrite) */
    protected $skipTables = ['migrations'];

    public function handle(): int
    {
        if (! config('database.connections.mysql_old.host')) {
            $this->error('Old DB not configured. Set OLD_DB_HOST, OLD_DB_DATABASE, OLD_DB_USERNAME, OLD_DB_PASSWORD in .env');
            return self::FAILURE;
        }

        $this->info('Source (old): mysql_old');
        $this->info('Target (current): default connection');

        if (! $this->option('force') && ! $this->confirm('This will TRUNCATE and refill tables on the default DB. Continue?')) {
            return self::SUCCESS;
        }

        try {
            $tables = $this->getTablesFromOld();
        } catch (\Throwable $e) {
            $this->error('Cannot connect to old DB: ' . $e->getMessage());
            return self::FAILURE;
        }

        $tables = array_diff($tables, $this->skipTables);
        $chunk = (int) $this->option('chunk');

        DB::connection()->getPdo()->exec('SET FOREIGN_KEY_CHECKS=0');

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                $this->warn("Skip [{$table}] (not in target DB)");
                continue;
            }

            $this->info("Copying [{$table}] ...");
            try {
                DB::table($table)->truncate();
                $count = 0;
                DB::connection('mysql_old')
                    ->table($table)
                    ->orderBy(DB::raw('1'))
                    ->chunk($chunk, function ($rows) use ($table, &$count) {
                        $insert = $rows->map(fn ($r) => (array) $r)->toArray();
                        if (! empty($insert)) {
                            DB::table($table)->insert($insert);
                            $count += count($insert);
                        }
                    });
                $this->line("  -> {$count} rows");
            } catch (\Throwable $e) {
                $this->error("  Failed: " . $e->getMessage());
            }
        }

        DB::connection()->getPdo()->exec('SET FOREIGN_KEY_CHECKS=1');
        $this->info('Done.');
        return self::SUCCESS;
    }

    protected function getTablesFromOld(): array
    {
        $db = config('database.connections.mysql_old.database');
        $rows = DB::connection('mysql_old')->select('SHOW TABLES');
        $key = 'Tables_in_' . $db;
        return array_map(fn ($r) => $r->{$key}, $rows);
    }
}
