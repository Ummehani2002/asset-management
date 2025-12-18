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
    </style>
</head>
<body>
    <h2>Assets Report - {{ $category->category_name }}</h2>
    <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    <p><strong>Total Assets:</strong> {{ $assets->count() }}</p>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Asset ID</th>
                <th>Brand</th>
                <th>Purchase Date</th>
                <th>Warranty Start</th>
                <th>Expiry Date</th>
                <th>PO Number</th>
                <th>Serial Number</th>
            </tr>
        </thead>
        <tbody>
            @foreach($assets as $index => $asset)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $asset->asset_id ?? 'N/A' }}</td>
                    <td>{{ $asset->brand->name ?? 'N/A' }}</td>
                    <td>{{ $asset->purchase_date ?? 'N/A' }}</td>
                    <td>{{ $asset->warranty_start ?? 'N/A' }}</td>
                    <td>{{ $asset->expiry_date ?? 'N/A' }}</td>
                    <td>{{ $asset->po_number ?? 'N/A' }}</td>
                    <td>{{ $asset->serial_number ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

