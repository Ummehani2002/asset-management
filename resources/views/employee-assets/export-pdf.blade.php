<!DOCTYPE html>
<html>
<head>
    <title>Employee Assets Report - {{ $employeeName }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #1F2A44; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        h2 { color: #333; }
    </style>
</head>
<body>
    <h2>Employee Assets Report</h2>
    <p><strong>Employee:</strong> {{ $employeeName }}</p>
    <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    <p><strong>Total Assets:</strong> {{ count($assets) }}</p>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Asset ID</th>
                <th>Category</th>
                <th>Brand</th>
                <th>Serial Number</th>
                <th>PO Number</th>
                <th>Location</th>
                <th>Issue Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($assets as $index => $asset)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $asset['asset_id'] }}</td>
                    <td>{{ $asset['category'] }}</td>
                    <td>{{ $asset['brand'] }}</td>
                    <td>{{ $asset['serial_number'] }}</td>
                    <td>{{ $asset['po_number'] }}</td>
                    <td>{{ $asset['location'] }}</td>
                    <td>{{ $asset['issue_date'] }}</td>
                    <td>{{ $asset['status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

