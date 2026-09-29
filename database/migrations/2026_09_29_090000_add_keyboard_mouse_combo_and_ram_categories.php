<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('asset_categories')) {
            return;
        }

        $names = [
            'Keyboard and Mouse',
            'RAM',
        ];

        foreach ($names as $name) {
            $exists = DB::table('asset_categories')
                ->whereRaw('LOWER(TRIM(category_name)) = ?', [strtolower($name)])
                ->exists();

            if (! $exists) {
                DB::table('asset_categories')->insert([
                    'category_name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('asset_categories')) {
            return;
        }

        DB::table('asset_categories')
            ->whereIn('category_name', ['Keyboard and Mouse', 'RAM'])
            ->delete();
    }
};
