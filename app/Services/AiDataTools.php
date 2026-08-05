<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class AiDataTools
{
    public function definitions(bool $isAdmin): array
    {
        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'count_assets_by_status',
                    'description' => 'Count assets grouped by status (available, assigned, returned, under_maintenance, scrap). Returned is often treated as available in the UI.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'entity_name' => [
                                'type' => 'string',
                                'description' => 'Optional entity name filter (partial match).',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'count_assets_by_category',
                    'description' => 'Count assets by category name with optional status filter.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'category_name' => [
                                'type' => 'string',
                                'description' => 'Optional category name filter (e.g. Desktop, Laptop). Partial match.',
                            ],
                            'status' => [
                                'type' => 'string',
                                'description' => 'Optional status: available, assigned, returned, under_maintenance, scrap. Use available to include returned.',
                            ],
                            'entity_name' => [
                                'type' => 'string',
                                'description' => 'Optional entity name filter.',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        if ($isAdmin) {
            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'find_asset_by_serial_or_id',
                    'description' => 'Look up a single asset by asset_id or serial_number. Admin only. Returns basic fields only.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => [
                                'type' => 'string',
                                'description' => 'Asset ID or serial number (exact or partial).',
                            ],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ];

            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'entity_asset_summary',
                    'description' => 'Summarize asset totals for one entity by status. Admin only.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'entity_name' => [
                                'type' => 'string',
                                'description' => 'Entity name (partial match allowed).',
                            ],
                        ],
                        'required' => ['entity_name'],
                    ],
                ],
            ];
        }

        return $tools;
    }

    public function call(string $name, array $arguments, User $user): array
    {
        $isAdmin = $user->isAdmin();

        return match ($name) {
            'count_assets_by_status' => $this->countAssetsByStatus($arguments, $isAdmin),
            'count_assets_by_category' => $this->countAssetsByCategory($arguments, $isAdmin),
            'find_asset_by_serial_or_id' => $isAdmin
                ? $this->findAssetBySerialOrId($arguments)
                : ['error' => 'Only admins can look up individual assets. Ask an admin for details.'],
            'entity_asset_summary' => $isAdmin
                ? $this->entityAssetSummary($arguments)
                : ['error' => 'Only admins can view entity summaries. Ask an admin for a detailed report.'],
            default => ['error' => 'Unknown tool.'],
        };
    }

    private function baseAssetQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        if (!Schema::hasTable('assets')) {
            return null;
        }

        return Asset::query();
    }

    private function applyEntityFilter($query, ?string $entityName): void
    {
        $entityName = trim((string) $entityName);
        if ($entityName === '' || !Schema::hasTable('entities') || !Schema::hasColumn('assets', 'entity_id')) {
            return;
        }

        $entityIds = Entity::query()
            ->where('name', 'like', '%' . $entityName . '%')
            ->pluck('id');

        if ($entityIds->isEmpty()) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereIn('entity_id', $entityIds);
    }

    private function applyStatusFilter($query, ?string $status): void
    {
        $status = strtolower(trim((string) $status));
        if ($status === '') {
            return;
        }

        if ($status === 'available') {
            $query->whereIn('status', ['available', 'returned']);
            return;
        }

        $query->where('status', $status);
    }

    private function countAssetsByStatus(array $arguments, bool $isAdmin): array
    {
        $query = $this->baseAssetQuery();
        if (!$query) {
            return ['error' => 'Assets table is not available.'];
        }

        if (!$isAdmin && !empty($arguments['entity_name'])) {
            return [
                'note' => 'Non-admin users can only see org-wide high-level totals. Entity filters are admin-only.',
                'by_status' => $this->statusCounts(Asset::query()),
            ];
        }

        $this->applyEntityFilter($query, $arguments['entity_name'] ?? null);

        return [
            'by_status' => $this->statusCounts($query),
            'note' => 'UI treats "returned" as available in many screens.',
        ];
    }

    private function statusCounts($query): array
    {
        $rows = (clone $query)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $available = (int) (($rows['available'] ?? 0) + ($rows['returned'] ?? 0));

        return [
            'raw' => $rows,
            'available_including_returned' => $available,
            'assigned' => (int) ($rows['assigned'] ?? 0),
            'under_maintenance' => (int) ($rows['under_maintenance'] ?? 0),
            'scrap' => (int) ($rows['scrap'] ?? 0),
            'total' => (int) array_sum($rows),
        ];
    }

    private function countAssetsByCategory(array $arguments, bool $isAdmin): array
    {
        if (!Schema::hasTable('assets') || !Schema::hasTable('asset_categories')) {
            return ['error' => 'Required tables are not available.'];
        }

        $categoryName = trim((string) ($arguments['category_name'] ?? ''));
        $query = Asset::query()->with('category');

        if ($categoryName !== '') {
            $categoryIds = AssetCategory::query()
                ->where('category_name', 'like', '%' . $categoryName . '%')
                ->pluck('id');
            if ($categoryIds->isEmpty()) {
                return ['categories' => [], 'message' => 'No matching category found.'];
            }
            $query->whereIn('asset_category_id', $categoryIds);
        }

        if ($isAdmin) {
            $this->applyEntityFilter($query, $arguments['entity_name'] ?? null);
        }

        $this->applyStatusFilter($query, $arguments['status'] ?? null);

        $grouped = (clone $query)
            ->selectRaw('asset_category_id, COUNT(*) as total')
            ->groupBy('asset_category_id')
            ->pluck('total', 'asset_category_id');

        $names = AssetCategory::whereIn('id', $grouped->keys())->pluck('category_name', 'id');

        $categories = [];
        foreach ($grouped as $categoryId => $total) {
            $categories[] = [
                'category' => $names[$categoryId] ?? ('Category #' . $categoryId),
                'total' => (int) $total,
            ];
        }

        usort($categories, fn ($a, $b) => $b['total'] <=> $a['total']);

        if (!$isAdmin) {
            return [
                'categories' => $categories,
                'note' => 'High-level totals only. Ask an admin for entity-level or individual asset details.',
            ];
        }

        return ['categories' => $categories];
    }

    private function findAssetBySerialOrId(array $arguments): array
    {
        $qText = trim((string) ($arguments['query'] ?? ''));
        if ($qText === '') {
            return ['error' => 'query is required'];
        }

        if (!Schema::hasTable('assets')) {
            return ['error' => 'Assets table is not available.'];
        }

        $asset = Asset::with(['category', 'brand', 'entity'])
            ->where(function ($q) use ($qText) {
                $q->where('asset_id', $qText)
                    ->orWhere('serial_number', $qText)
                    ->orWhere('asset_id', 'like', '%' . $qText . '%')
                    ->orWhere('serial_number', 'like', '%' . $qText . '%');
            })
            ->orderBy('id')
            ->first();

        if (!$asset) {
            return ['found' => false, 'message' => 'No asset matched that ID/serial.'];
        }

        return [
            'found' => true,
            'asset' => [
                'asset_id' => $asset->asset_id,
                'serial_number' => $asset->serial_number,
                'status' => $asset->status,
                'category' => $asset->category->category_name ?? null,
                'brand' => $asset->brand->name ?? null,
                'model_number' => $asset->model_number,
                'entity' => $asset->entity->name ?? null,
            ],
        ];
    }

    private function entityAssetSummary(array $arguments): array
    {
        $name = trim((string) ($arguments['entity_name'] ?? ''));
        if ($name === '') {
            return ['error' => 'entity_name is required'];
        }

        if (!Schema::hasTable('entities') || !Schema::hasTable('assets')) {
            return ['error' => 'Required tables are not available.'];
        }

        $entity = Entity::query()
            ->where('name', 'like', '%' . $name . '%')
            ->orderBy('name')
            ->first();

        if (!$entity) {
            return ['found' => false, 'message' => 'No entity matched that name.'];
        }

        $query = Asset::query()->where('entity_id', $entity->id);

        return [
            'found' => true,
            'entity' => $entity->name,
            'summary' => $this->statusCounts($query),
        ];
    }
}
