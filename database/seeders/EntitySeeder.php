<?php

namespace Database\Seeders;

use App\Models\Entity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class EntitySeeder extends Seeder
{
    /**
     * Seed default entities. Safe to run in production - won't create duplicates.
     */
    public function run(): void
    {
        if (!Schema::hasTable('entities')) {
            return;
        }

        $defaults = [
            'proscape',
            'water in motion',
            'bioscape',
            'tanseeq realty',
            'transmech',
            'timbertech',
            'ventana',
            'garden center',
        ];

        foreach ($defaults as $name) {
            Entity::firstOrCreate(
                ['name' => $name],
                ['name' => $name]
            );
        }
    }
}
