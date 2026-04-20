<!DOCTYPE html>
<html>
<head>
    <title>Service History Report</title>
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
    <h2>Service History (All Types)</h2>
    <p><strong>Account / search:</strong> {{ $accountLabel }}</p>
    <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Account No.</th>
                <th>Service Type</th>
                <th>Project</th>
                <th>Person in Charge</th>
                <th>Transaction</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
                <th>MRC</th>
                <th>Cost</th>
            </tr>
        </thead>
        <tbody>
            @foreach($historyRows as $index => $history)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $history->account_number ?? 'N/A' }}</td>
                    <td>{{ ucfirst($history->service_type ?? 'N/A') }}</td>
                    <td>{{ $history->project_name ?? 'N/A' }}</td>
                    <td>{{ $history->person_in_charge ?? 'N/A' }}</td>
                    <td>{{ ucfirst($history->transaction_type ?? 'N/A') }}</td>
                    <td>{{ $history->service_start_date ? $history->service_start_date->format('Y-m-d') : 'N/A' }}</td>
                    <td>{{ $history->service_end_date ? $history->service_end_date->format('Y-m-d') : 'Ongoing' }}</td>
                    <td>{{ ucfirst($history->status ?? 'N/A') }}</td>
                    <td>{{ number_format($history->mrc ?? 0, 2) }}</td>
                    <td>{{ number_format($history->cost ?? 0, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
