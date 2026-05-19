<?php

namespace App\Helpers;

use App\Models\Entity;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class EntityHelper
{
    /**
     * Get list of all entities/companies (from DB; fallback to default list if table empty)
     *
     * @return array
     */
    public static function getEntities()
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('entities')) {
                return self::defaultEntities();
            }
            $names = Entity::orderBy('name')->pluck('name')->toArray();
            return $names ?: self::defaultEntities();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('EntityHelper::getEntities failed: ' . $e->getMessage());
            return self::defaultEntities();
        }
    }

    /**
     * Default entity names (used when entities table is missing or empty)
     *
     * @return array
     */
    public static function defaultEntities()
    {
        return [
            'proscape',
            'water in motion',
            'bioscape',
            'tanseeq realty',
            'transmech',
            'timbertech',
            'ventana',
            'garden center',
        ];
    }

    /**
     * Get entities as options for select dropdown
     * 
     * @return array
     */
    public static function getEntityOptions()
    {
        $entities = self::getEntities();
        $options = [];
        foreach ($entities as $entity) {
            $options[$entity] = ucwords($entity);
        }
        return $options;
    }

    /**
     * Entity Master records for dropdowns (id + name), ordered by name.
     */
    public static function getEntityRecords(): Collection
    {
        try {
            if (!Schema::hasTable('entities')) {
                return collect();
            }

            return Entity::orderBy('name')->get();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('EntityHelper::getEntityRecords failed: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Employee IDs whose entity_name matches an Entity Master record (case-insensitive).
     */
    public static function employeeIdsForEntityId(int $entityId): array
    {
        if (!Schema::hasTable('entities') || !Schema::hasTable('employees')) {
            return [];
        }

        $entity = Entity::find($entityId);
        if (!$entity || trim((string) $entity->name) === '') {
            return [];
        }

        return Employee::whereRaw('LOWER(TRIM(entity_name)) = LOWER(?)', [trim($entity->name)])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * First employee id for an entity (used when storing entity_budgets.employee_id).
     */
    public static function representativeEmployeeIdForEntityId(int $entityId): ?int
    {
        $ids = self::employeeIdsForEntityId($entityId);
        return $ids[0] ?? null;
    }

    /**
     * Entity Master id for an employee's entity_name, if it exists.
     */
    public static function masterEntityIdForEmployeeName(?string $entityName): ?int
    {
        if (!Schema::hasTable('entities') || !$entityName || trim($entityName) === '') {
            return null;
        }

        return Entity::whereRaw('LOWER(TRIM(name)) = LOWER(?)', [trim($entityName)])->value('id');
    }
}
