<!DOCTYPE html>
<html>
<head>
    <title>Locations Report</title>
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
    <h2>Locations Report</h2>
    <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    <p><strong>Total Locations:</strong> {{ count($locations) }}</p>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Entity</th>
                <th>Country</th>
                <th>Location Name</th>
            </tr>
        </thead>
        <tbody>
            @foreach($locations as $index => $location)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $location->location_entity ?? 'N/A' }}</td>
                    <td>{{ $location->location_country ?? 'N/A' }}</td>
                    <td>{{ $location->location_name ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

