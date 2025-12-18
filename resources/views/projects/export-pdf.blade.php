<!DOCTYPE html>
<html>
<head>
    <title>Projects Report</title>
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
    <h2>Projects Report</h2>
    <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Project ID</th>
                <th>Project Name</th>
                <th>Entity</th>
                <th>Project Manager</th>
                <th>PC Secretary</th>
            </tr>
        </thead>
        <tbody>
            @foreach($projects as $index => $project)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $project->project_id }}</td>
                    <td>{{ $project->project_name }}</td>
                    <td>{{ $project->entity ?? 'N/A' }}</td>
                    <td>{{ $project->project_manager ?? 'N/A' }}</td>
                    <td>{{ $project->pc_secretary ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

