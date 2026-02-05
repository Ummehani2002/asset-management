<!DOCTYPE html>
<html>
<head>
    <title>Internet Services Report</title>
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
    <h2>Internet Services Report</h2>
    <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Project</th>
                <th>Entity</th>
                <th>Service Type</th>
                <th>Bandwidth</th>
                <th>Transaction Type</th>
                <th>Account Number</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Person in Charge</th>
                <th>Status</th>
                <th>MRC</th>
                <th>Cost</th>
            </tr>
        </thead>
        <tbody>
            @foreach($internetServices as $index => $service)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $service->project_name ?? 'N/A' }}</td>
                    <td>{{ $service->entity ?? 'N/A' }}</td>
                    <td>{{ ucfirst($service->service_type ?? 'N/A') }}</td>
                    <td>{{ $service->bandwidth ?? 'N/A' }}</td>
                    <td>{{ ucfirst($service->transaction_type ?? 'N/A') }}</td>
                    <td>{{ $service->account_number ?? 'N/A' }}</td>
                    <td>{{ $service->service_start_date ? $service->service_start_date->format('Y-m-d') : 'N/A' }}</td>
                    <td>{{ $service->service_end_date ? $service->service_end_date->format('Y-m-d') : 'Ongoing' }}</td>
                    <td>{{ $service->person_in_charge ?? 'N/A' }}</td>
                    <td>{{ ucfirst($service->status ?? 'N/A') }}</td>
                    <td>{{ number_format($service->mrc ?? 0, 2) }}</td>
                    <td>{{ number_format($service->cost ?? 0, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

