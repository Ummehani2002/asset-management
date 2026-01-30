<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seed asset_categories with standard categories and their three-letter codes
     * (codes are applied via AssetController::getCategoryPrefix when generating asset IDs).
     */
    public function up(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('asset_categories')) {
            return;
        }

        $categories = [
            'Server',
            'Desktop',
            'Laptop',
            'Workstation',
            'Tablet / iPad',
            'Mobile Phone',
            'Router',
            'Firewall',
            'Managed Switch',
            'Unmanaged Switch',
            'PoE Switch',
            'Access Point',
            'Range Extender',
            '4G/5G Router',
            'Load Balancer',
            'NAS Storage',
            'SAN Storage',
            'External Hard Disk',
            'Internal HDD/SSD',
            'Backup Device / Tape',
            'Printer',
            'Plotter',
            'Scanner',
            'All-in-One Printer',
            'UPS',
            'PDU',
            'Server Rack',
            'Rack Accessories',
            'PABX',
            'Telephone',
            'Video Conferencing',
            'Interactive Panel / Smart TV',
            'CCTV Camera',
            'NVR / DVR',
            'Projector',
            'Monitor',
            'Keyboard',
            'Mouse',
            'Docking Station',
            'Webcam',
            'Headset',
            'Virtual Machine',
            'License',
            'Cloud Subscription',
            'SSL Certificate',
            'Domain',
            'Public IP',
            'KVM Switch',
            'Network Cable',
            'SFP Module',
            'DR Site',
        ];

        $now = now();
        $rows = [];
        foreach ($categories as $name) {
            $rows[] = [
                'category_name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('asset_categories')->insertOrIgnore($rows);
    }

    /**
     * Reverse the migration (optional: do not remove categories that may be in use).
     */
    public function down(): void
    {
        // Leave existing categories intact; no-op.
    }
};
