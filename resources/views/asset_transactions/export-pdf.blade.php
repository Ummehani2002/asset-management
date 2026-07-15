<!DOCTYPE html>
<html>
<head>
    <title>Asset Transactions Report - {{ ucfirst($assetStatus !== 'all' ? str_replace('_', ' ', $assetStatus) : 'All') }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #222; margin: 0; padding: 0; }
        h2 { color: #1F2A44; margin: 0 0 8px; font-size: 18px; }
        .meta { margin: 0 0 6px; }
        .transaction-card {
            margin-top: 18px;
            border: 1px solid #ccc;
            page-break-inside: avoid;
        }
        .transaction-card + .transaction-card {
            margin-top: 24px;
        }
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
        .detail-table td {
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <h2>Asset Transactions Report</h2>
    <p class="meta"><strong>Status Filter:</strong> {{ ucfirst($assetStatus !== 'all' ? str_replace('_', ' ', $assetStatus) : 'All Transactions') }}</p>
    <p class="meta">Generated on: {{ date('Y-m-d H:i:s') }}</p>
    <p class="meta"><strong>Total Transactions:</strong> {{ count($transactions) }}</p>

    @foreach($transactions as $index => $t)
        @php
            $assignedTo = $t->transaction_type === 'system_maintenance'
                ? (($t->employee->name ?? 'N/A') . ' (Maintenance)')
                : ($t->employee->name ?? $t->project_name ?? 'N/A');
            $entity = ucwords(trim(optional($t->location)->location_entity ?? $t->employee->entity_name ?? '')) ?: 'N/A';
        @endphp
        <div class="transaction-card">
            <div class="card-title">Transaction {{ $index + 1 }} — ID {{ $t->id }}</div>
            <table class="detail-table">
                <tr>
                    <th>Transaction ID</th>
                    <td>{{ $t->id }}</td>
                </tr>
                <tr>
                    <th>Asset ID</th>
                    <td>{{ $t->asset->asset_id ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Serial Number</th>
                    <td>{{ $t->asset->serial_number ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Category</th>
                    <td>{{ $t->asset->assetCategory->category_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Model Number</th>
                    <td>{{ $t->asset ? $t->asset->resolveDisplayModel() : 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Features</th>
                    <td>{{ $t->asset ? $t->asset->resolveFeaturesSummary() : 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Transaction Type</th>
                    <td>{{ ucfirst(str_replace('_', ' ', $t->transaction_type)) }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>{{ ucfirst(str_replace('_', ' ', $t->asset->status ?? 'N/A')) }}</td>
                </tr>
                <tr>
                    <th>Employee ID</th>
                    <td>{{ $t->employee->employee_id ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Assigned To</th>
                    <td>{{ $assignedTo }}</td>
                </tr>
                <tr>
                    <th>Entity</th>
                    <td>{{ $entity }}</td>
                </tr>
                <tr>
                    <th>Location</th>
                    <td>{{ $t->location->location_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Issue Date</th>
                    <td>{{ $t->issue_date ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Return Date</th>
                    <td>{{ $t->return_date ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Receive Date</th>
                    <td>{{ $t->receive_date ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Received By</th>
                    <td>{{ $t->received_by ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Delivery Date</th>
                    <td>{{ $t->delivery_date ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Created At</th>
                    <td>{{ $t->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
            </table>
        </div>
    @endforeach
</body>
</html>
