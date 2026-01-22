<?php

namespace App\Helpers;

class EntityHelper
{
    /**
     * Get list of all entities/companies
     * 
     * @return array
     */
    public static function getEntities()
    {
        return [
            'proscape',
            'water in motion',
            'bioscape',
            'tanseeq realty',
            'transmech',
            'timbertech',
            'ventana',
            'garden center'
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
