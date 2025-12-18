<!DOCTYPE html>
<html>
<head>
    <title>Asset Transactions Report - {{ ucfirst($assetStatus !== 'all' ? str_replace('_', ' ', $assetStatus) : 'All') }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #1F2A44; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        h2 { color: #333; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 10px; }
    </style>
</head>
<body>
    <h2>Asset Transactions Report</h2>
    <p><strong>Status Filter:</strong> {{ ucfirst($assetStatus !== 'all' ? str_replace('_', ' ', $assetStatus) : 'All Transactions') }}</p>
    <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    <p><strong>Total Transactions:</strong> {{ count($transactions) }}</p>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Transaction ID</th>
                <th>Asset ID</th>
                <th>Serial Number</th>
                <th>Category</th>
                <th>Transaction Type</th>
                <th>Status</th>
                <th>Employee/Project</th>
                <th>Location</th>
                <th>Issue Date</th>
                <th>Return Date</th>
                <th>Receive Date</th>
                <th>Delivery Date</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $index => $t)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $t->id }}</td>
                    <td>{{ $t->asset->asset_id ?? 'N/A' }}</td>
                    <td>{{ $t->asset->serial_number ?? 'N/A' }}</td>
                    <td>{{ $t->asset->assetCategory->category_name ?? 'N/A' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $t->transaction_type)) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $t->asset->status ?? 'N/A')) }}</td>
                    <td>{{ $t->employee->name ?? $t->project_name ?? 'N/A' }}</td>
                    <td>{{ $t->location->location_name ?? 'N/A' }}</td>
                    <td>{{ $t->issue_date ?? 'N/A' }}</td>
                    <td>{{ $t->return_date ?? 'N/A' }}</td>
                    <td>{{ $t->receive_date ?? 'N/A' }}</td>
                    <td>{{ $t->delivery_date ?? 'N/A' }}</td>
                    <td>{{ $t->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

