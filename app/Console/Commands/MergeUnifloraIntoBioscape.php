<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\Entity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MergeUnifloraIntoBioscape extends Command
{
    protected $signature = 'entities:merge-uniflora-into-bioscape
                            {--dry-run : Preview changes without writing}
                            {--keep-name=BIOSCAPE : Canonical Bioscape entity name}';

    protected $description = 'Move all Uniflora data (assets, employees, locations, etc.) into Bioscape and remove Uniflora entities';

    public function handle(): int
    {
        if (! Schema::hasTable('entities')) {
            $this->error('entities table not found.');

            return 1;
        }

        $keepName = trim((string) $this->option('keep-name')) ?: 'BIOSCAPE';
        $dryRun = (bool) $this->option('dry-run');

        $unifloraEntities = Entity::query()
            ->whereRaw('LOWER(name) LIKE ?', ['%uniflora%'])
            ->orderBy('id')
            ->get();

        $bioscapeEntities = Entity::query()
            ->whereRaw('LOWER(name) LIKE ?', ['%bioscape%'])
            ->orderBy('id')
            ->get();

        $this->info('Uniflora entities: '.$unifloraEntities->map(fn ($e) => "{$e->id}:{$e->name}")->implode(', ') ?: '(none)');
        $this->info('Bioscape entities: '.$bioscapeEntities->map(fn ($e) => "{$e->id}:{$e->name}")->implode(', ') ?: '(none)');

        if ($unifloraEntities->isEmpty()) {
            $this->warn('No Uniflora entities found. Will still normalize Bioscape name variants and string columns if needed.');
        }

        $stringTables = [
            'employees' => 'entity_name',
            'locations' => 'location_entity',
            'projects' => 'entity',
            'internet_services' => 'entity',
            'issue_notes' => 'entity',
        ];

        $preview = [];
        foreach ($stringTables as $table => $column) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            $preview[$table] = [
                'uniflora' => DB::table($table)->whereRaw("LOWER(TRIM({$column})) LIKE ?", ['%uniflora%'])->count(),
                'bioscape_variants' => DB::table($table)
                    ->whereRaw("LOWER(TRIM({$column})) LIKE ?", ['%bioscape%'])
                    ->whereRaw("LOWER(TRIM({$column})) != ?", [strtolower($keepName)])
                    ->count(),
            ];
        }

        $unifloraIds = $unifloraEntities->pluck('id');
        $assetMoveCount = $unifloraIds->isNotEmpty() && Schema::hasColumn('assets', 'entity_id')
            ? Asset::whereIn('entity_id', $unifloraIds)->count()
            : 0;

        $this->table(
            ['Scope', 'Count'],
            collect($preview)->flatMap(fn ($counts, $table) => [
                ["{$table} Uniflora strings", $counts['uniflora']],
                ["{$table} other Bioscape variants → {$keepName}", $counts['bioscape_variants']],
            ])->merge([
                ['assets.entity_id on Uniflora', $assetMoveCount],
                ['Uniflora entity rows to delete', $unifloraEntities->count()],
            ])->values()->all()
        );

        if ($dryRun) {
            $this->warn('[Dry run] No changes written.');

            return 0;
        }

        DB::transaction(function () use ($keepName, $unifloraEntities, $bioscapeEntities, $stringTables, $unifloraIds) {
            $keep = Entity::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($keepName)])
                ->first();

            if (! $keep) {
                // Prefer renaming an existing Bioscape row instead of creating a duplicate
                $keep = $bioscapeEntities->first();
                if ($keep) {
                    $keep->name = $keepName;
                    $keep->save();
                    $this->info("Renamed entity #{$keep->id} to {$keepName}");
                } else {
                    $managerId = $unifloraEntities->first(fn ($e) => $e->asset_manager_id)?->asset_manager_id;
                    $keep = Entity::create([
                        'name' => $keepName,
                        'asset_manager_id' => $managerId,
                    ]);
                    $this->info("Created entity #{$keep->id} {$keepName}");
                }
            }

            if (! $keep->asset_manager_id) {
                $managerId = $unifloraEntities->first(fn ($e) => $e->asset_manager_id)?->asset_manager_id
                    ?? $bioscapeEntities->first(fn ($e) => $e->asset_manager_id)?->asset_manager_id;
                if ($managerId) {
                    $keep->asset_manager_id = $managerId;
                    $keep->save();
                }
            }

            if (Schema::hasColumn('assets', 'entity_id') && $unifloraIds->isNotEmpty()) {
                $moved = Asset::whereIn('entity_id', $unifloraIds)->update(['entity_id' => $keep->id]);
                $this->info("Moved {$moved} asset(s) to {$keepName}");
            }

            // Also point any other bioscape entity_id variants at keep
            $otherBioscapeIds = Entity::query()
                ->whereRaw('LOWER(name) LIKE ?', ['%bioscape%'])
                ->where('id', '!=', $keep->id)
                ->pluck('id');
            if ($otherBioscapeIds->isNotEmpty() && Schema::hasColumn('assets', 'entity_id')) {
                $moved = Asset::whereIn('entity_id', $otherBioscapeIds)->update(['entity_id' => $keep->id]);
                $this->info("Moved {$moved} bioscape-variant asset(s) to {$keepName}");
            }

            foreach ($stringTables as $table => $column) {
                if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $fromUniflora = DB::table($table)
                    ->whereRaw("LOWER(TRIM({$column})) LIKE ?", ['%uniflora%'])
                    ->update([$column => $keepName]);

                $fromBioscapeVariant = DB::table($table)
                    ->whereRaw("LOWER(TRIM({$column})) LIKE ?", ['%bioscape%'])
                    ->whereRaw("LOWER(TRIM({$column})) != ?", [strtolower($keepName)])
                    ->update([$column => $keepName]);

                $this->info("{$table}.{$column}: Uniflora→{$keepName}={$fromUniflora}, Bioscape variants→{$keepName}={$fromBioscapeVariant}");
            }

            // Delete Uniflora rows and extra Bioscape name variants (keep canonical only)
            $deleteIds = $unifloraEntities->pluck('id')
                ->merge($otherBioscapeIds ?? collect())
                ->unique()
                ->reject(fn ($id) => (int) $id === (int) $keep->id)
                ->values();

            if ($deleteIds->isNotEmpty()) {
                Entity::whereIn('id', $deleteIds)->delete();
                $this->info('Deleted entity ids: '.$deleteIds->implode(', '));
            }
        });

        $this->info("Done. Uniflora merged into {$keepName}.");

        return 0;
    }
}
