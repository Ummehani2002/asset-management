<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Asset Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        h2 { color: #333; margin-bottom: 4px; }
        .meta { margin: 2px 0; color: #555; }
    </style>
</head>
<body>
    <h2>Dashboard Asset Report</h2>
    <p class="meta"><strong>Entity:</strong> {{ $selectedEntity ? ucwords($selectedEntity->name) : 'All Entities' }}</p>
    <p class="meta"><strong>Total Assets:</strong> {{ $assets->count() }}</p>
    <p class="meta">Generated on: {{ date('Y-m-d H:i:s') }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Asset ID</th>
                <th>Entity</th>
                <th>Category</th>
                <th>Brand</th>
                <th>Serial Number</th>
                <th>Status</th>
                <th>Purchase Date</th>
                <th>Warranty Start</th>
                <th>Expiry Date</th>
                <th>PO Number</th>
                <th>Vendor Name</th>
                <th>Value</th>
            </tr>
        </thead>
        <tbody>
            @forelse($assets as $index => $asset)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $asset->asset_id ?? 'N/A' }}</td>
                    <td>{{ $asset->entity->name ?? $asset->location->location_entity ?? 'N/A' }}</td>
                    <td>{{ $asset->category->category_name ?? 'N/A' }}</td>
                    <td>{{ $asset->brand->name ?? 'N/A' }}</td>
                    <td>{{ $asset->serial_number ?? 'N/A' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $asset->status ?? 'N/A')) }}</td>
                    <td>{{ $asset->purchase_date ?? 'N/A' }}</td>
                    <td>{{ $asset->warranty_start ?? 'N/A' }}</td>
                    <td>{{ $asset->expiry_date ?? 'N/A' }}</td>
                    <td>{{ $asset->po_number ?? 'N/A' }}</td>
                    <td>{{ $asset->vendor_name ?? '-' }}</td>
                    <td>{{ $asset->value ? number_format($asset->value, 2) : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" style="text-align: center;">No assets found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

