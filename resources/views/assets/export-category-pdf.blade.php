<!DOCTYPE html>
<html>
<head>
    <title>Assets Report - {{ $category->category_name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        h2 { color: #333; }
        .stats { margin: 12px 0; padding: 10px; background: #f5f5f5; border: 1px solid #ddd; }
        .stats span { margin-right: 20px; }
    </style>
</head>
<body>
    <h2>Assets Report - {{ $category->category_name }}</h2>
    <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    <div class="stats">
        <strong>Filter:</strong> {{ $statusLabel ?? 'All statuses' }}<br>
        <span><strong>Total in report:</strong> {{ $exportStats['total'] ?? $assets->count() }}</span>
        <span><strong>Assigned:</strong> {{ $exportStats['assigned'] ?? 0 }}</span>
        <span><strong>Available:</strong> {{ $exportStats['available'] ?? 0 }}</span>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Asset ID</th>
                <th>Entity</th>
                <th>Status</th>
                <th>Brand</th>
                <th>Model</th>
                <th>Features</th>
                <th>Purchase Date</th>
                <th>Warranty Start</th>
                <th>Expiry Date</th>
                <th>PO Number</th>
                <th>Employee Details</th>
                <th>Vendor Name</th>
                <th>Value</th>
                <th>Serial Number</th>
            </tr>
        </thead>
        <tbody>
            @foreach($assets as $index => $asset)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $asset->asset_id ?? 'N/A' }}</td>
                    <td>{{ $asset->entity->name ?? $asset->location->location_entity ?? 'N/A' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $asset->status ?? 'N/A')) }}</td>
                    <td>{{ $asset->brand->name ?? 'N/A' }}</td>
                    <td>{{ $asset->display_model }}</td>
                    <td style="font-size: 10px; max-width: 220px;">
                        @forelse($asset->featureValues ?? [] as $fv)
                            <div><strong>{{ $fv->feature->feature_name ?? 'N/A' }}</strong>: {{ $fv->feature_value ?? '—' }}</div>
                        @empty
                            —
                        @endforelse
                    </td>
                    <td>{{ $asset->purchase_date ?? 'N/A' }}</td>
                    <td>{{ $asset->warranty_start ?? 'N/A' }}</td>
                    <td>{{ $asset->expiry_date ?? 'N/A' }}</td>
                    <td>{{ $asset->po_number ?? 'N/A' }}</td>
                    <td>
                        @php
                            $latestTxnEmployee = $asset->latestTransaction?->employee;
                        @endphp
                        @if($latestTxnEmployee)
                            {{ $latestTxnEmployee->employee_id ?? 'N/A' }} - {{ $latestTxnEmployee->name ?? 'N/A' }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td>{{ $asset->vendor_name ?? '-' }}</td>
                    <td>{{ $asset->value ? number_format($asset->value, 2) : '-' }}</td>
                    <td>{{ $asset->serial_number ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
