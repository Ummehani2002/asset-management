<!DOCTYPE html>
<html>
<head>
    <title>Employees Report</title>
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
    <h2>Employees Report</h2>
    <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    <p><strong>Total Employees:</strong> {{ $employees->count() }}</p>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Employee ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Entity</th>
                <th>Department</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $index => $employee)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $employee->employee_id }}</td>
                    <td>{{ $employee->name ?? 'N/A' }}</td>
                    <td>{{ $employee->email ?? 'N/A' }}</td>
                    <td>{{ $employee->phone ?? 'N/A' }}</td>
                    <td>{{ $employee->entity_name ?? 'N/A' }}</td>
                    <td>{{ $employee->department_name ?? 'N/A' }}</td>
                    <td>{{ $employee->created_at ? $employee->created_at->format('Y-m-d') : 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

