<!DOCTYPE html>
<html>
<head>
    <title>Assets Report - {{ $category->category_name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #222; margin: 0; padding: 0; }
        h2 { color: #1F2A44; margin: 0 0 8px; font-size: 18px; }
        .meta { margin: 0 0 6px; }
        .asset-card {
            margin-top: 18px;
            border: 1px solid #ccc;
            page-break-inside: avoid;
        }
        .asset-card + .asset-card { margin-top: 24px; }
        .card-title {
            background-color: #1F2A44;
            color: #fff;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: bold;
        }
        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }
        .detail-table th,
        .detail-table td {
            border: 1px solid #ddd;
            padding: 7px 10px;
            text-align: left;
            vertical-align: top;
        }
        .detail-table th {
            width: 30%;
            background-color: #f4f6f8;
            font-weight: bold;
        }
        .detail-table td { word-wrap: break-word; }
    </style>
</head>
<body>
    <h2>Assets Report - {{ $category->category_name }}</h2>
    <p class="meta">Generated on: {{ date('Y-m-d H:i:s') }}</p>
    <p class="meta"><strong>Total Assets:</strong> {{ $assets->count() }}</p>

    @foreach($assets as $index => $asset)
        @php
            $latestTxnEmployee = $asset->latestTransaction?->employee;
            $statusLabel = $asset->status === 'assigned'
                ? 'Assigned'
                : (in_array($asset->status, ['available', 'returned'], true) || ($asset->latestTransaction?->transaction_type ?? null) === 'return'
                    ? 'Available'
                    : ucfirst(str_replace('_', ' ', $asset->status ?? 'N/A')));
        @endphp
        <div class="asset-card">
            <div class="card-title">Asset {{ $index + 1 }} — {{ $asset->asset_id ?? 'N/A' }}</div>
            <table class="detail-table">
                <tr>
                    <th>Asset ID</th>
                    <td>{{ $asset->asset_id ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Entity</th>
                    <td>{{ $asset->entity->name ?? $asset->location->location_entity ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>{{ $statusLabel }}</td>
                </tr>
                <tr>
                    <th>Brand</th>
                    <td>{{ $asset->brand->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Model</th>
                    <td>{{ $asset->display_model }}</td>
                </tr>
                <tr>
                    <th>Features</th>
                    <td>
                        @php $featureEntries = $asset->resolveFeatureEntries(); @endphp
                        @forelse($featureEntries as $entry)
                            <div><strong>{{ $entry['label'] }}</strong>: {{ $entry['value'] }}</div>
                        @empty
                            N/A
                        @endforelse
                    </td>
                </tr>
                <tr>
                    <th>Purchase Date</th>
                    <td>{{ $asset->purchase_date ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Warranty Start</th>
                    <td>{{ $asset->warranty_start ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Expiry Date</th>
                    <td>{{ $asset->expiry_date ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Aging</th>
                    <td>{{ $asset->agingLabel() }}</td>
                </tr>
                <tr>
                    <th>PO Number</th>
                    <td>{{ $asset->po_number ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Employee Details</th>
                    <td>
                        @if($latestTxnEmployee)
                            {{ $latestTxnEmployee->employee_id ?? 'N/A' }} - {{ $latestTxnEmployee->name ?? 'N/A' }}
                        @else
                            N/A
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Vendor Name</th>
                    <td>{{ $asset->vendor_name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Value</th>
                    <td>{{ $asset->value ? number_format($asset->value, 2) : '-' }}</td>
                </tr>
                <tr>
                    <th>Serial Number</th>
                    <td>{{ $asset->serial_number ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>
    @endforeach
</body>
</html>
