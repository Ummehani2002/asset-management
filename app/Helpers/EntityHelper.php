<?php

namespace App\Helpers;

use App\Models\Entity;

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
}
