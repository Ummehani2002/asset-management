<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class EntityController extends Controller
{
    public function index()
    {
        $entities = Entity::orderBy('name')->get();
        return view('entity-master.index', compact('entities'));
    }

    public function syncFromCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt',
            'replace_existing' => 'nullable|boolean',
        ]);

        $replace = (bool) $request->replace_existing;

        try {
            $content = file_get_contents($request->file('file')->getRealPath());
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
            $tempPath = sys_get_temp_dir() . '/entity_sync_' . uniqid() . '.csv';
            file_put_contents($tempPath, $content);

            $handle = fopen($tempPath, 'r');
            if (!$handle) {
                @unlink($tempPath);
                return back()->with('error', 'Could not read file.');
            }

            $headers = array_map('trim', fgetcsv($handle));
            $entityNames = [];
            $normalize = function ($key) use ($headers) {
                foreach ($headers as $i => $h) {
                    if (str_replace(' ', '', strtolower($h)) === str_replace(' ', '', strtolower($key))) {
                        return $i;
                    }
                }
                return null;
            };

            $entityCol = $normalize('Entity') ?? $normalize('Entity Name') ?? $normalize('Company');
            if ($entityCol === null) {
                fclose($handle);
                @unlink($tempPath);
                return back()->with('error', 'No Entity, Entity Name, or Company column found in file.');
            }

            while (($row = fgetcsv($handle)) !== false) {
                $val = trim($row[$entityCol] ?? '');
                if ($val && !in_array($val, $entityNames, true)) {
                    $entityNames[] = $val;
                }
            }
            fclose($handle);
            @unlink($tempPath);

            if (empty($entityNames)) {
                return back()->with('error', 'No entity values found in file.');
            }

            if ($replace && Schema::hasTable('entities')) {
                Entity::query()->delete();
            }

            $added = 0;
            foreach ($entityNames as $name) {
                if (Entity::where('name', $name)->exists()) continue;
                Entity::create(['name' => $name]);
                $added++;
            }

            return back()->with('success', "Entities updated. {$added} new entities added." . ($replace ? ' (Replaced existing.)' : ''));
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function syncFromEmployees(Request $request)
    {
        if (!Schema::hasTable('employees')) {
            return back()->with('error', 'Employees table not found.');
        }

        $replace = (bool) $request->replace_existing;
        $entityNames = \App\Models\Employee::whereNotNull('entity_name')
            ->where('entity_name', '!=', '')
            ->distinct()
            ->pluck('entity_name')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($entityNames)) {
            return back()->with('error', 'No entity names found in employees.');
        }

        try {
            if ($replace) {
                Entity::query()->delete();
            }
            $added = 0;
            foreach ($entityNames as $name) {
                if (Entity::where('name', $name)->exists()) continue;
                Entity::create(['name' => $name]);
                $added++;
            }
            return back()->with('success', "Synced {$added} entities from employees." . ($replace ? ' (Replaced existing.)' : ''));
        } catch (\Exception $e) {
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:entities,name',
        ]);

        Entity::create(['name' => trim($request->name)]);

        return redirect()->route('entity-master.index')->with('success', 'Entity created successfully.');
    }

    public function edit($id)
    {
        $entity = Entity::findOrFail($id);
        return view('entity-master.edit', compact('entity'));
    }

    public function update(Request $request, $id)
    {
        $entity = Entity::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:100|unique:entities,name,' . $entity->id,
        ]);

        $entity->update(['name' => trim($request->name)]);

        return redirect()->route('entity-master.index')->with('success', 'Entity updated successfully.');
    }

    public function destroy($id)
    {
        $entity = Entity::findOrFail($id);
        $entity->delete();
        return redirect()->route('entity-master.index')->with('success', 'Entity deleted successfully.');
    }
}
