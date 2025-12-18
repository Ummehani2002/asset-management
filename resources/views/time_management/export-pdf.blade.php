<!DOCTYPE html>
<html>
<head>
    <title>Time Management Report - {{ ucfirst($status ?? 'All') }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #1F2A44; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        tr.delayed { background-color: #ffebee; }
        h2 { color: #333; }
        .text-danger { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Time Management Report - {{ ucfirst($status ?? 'All Tasks') }}</h2>
    <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    <p><strong>Total Tasks:</strong> {{ count($tasks) }}</p>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Ticket</th>
                <th>Employee</th>
                <th>Project</th>
                <th>Job Date</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Duration (hrs)</th>
                <th>Status</th>
                <th>Performance (%)</th>
                <th>Delayed (Days)</th>
                <th>Delay Reason</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tasks as $index => $task)
                <tr class="{{ ($task->delayed_days && $task->delayed_days > 0) ? 'delayed' : '' }}">
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $task->ticket_number ?? 'N/A' }}</td>
                    <td>{{ $task->employee_name ?? 'N/A' }}</td>
                    <td>{{ $task->project_name ?? 'N/A' }}</td>
                    <td>{{ $task->job_card_date ? $task->job_card_date->format('Y-m-d') : 'N/A' }}</td>
                    <td>{{ $task->start_time ? $task->start_time->setTimezone('Asia/Dubai')->format('Y-m-d H:i') : 'N/A' }}</td>
                    <td>{{ $task->end_time ? $task->end_time->setTimezone('Asia/Dubai')->format('Y-m-d H:i') : 'N/A' }}</td>
                    <td>{{ $task->duration_hours ?? 'N/A' }}</td>
                    <td>{{ ucfirst($task->status ?? 'N/A') }}</td>
                    <td>{{ $task->performance_percent ?? 'N/A' }}</td>
                    <td class="{{ ($task->delayed_days && $task->delayed_days > 0) ? 'text-danger' : '' }}">
                        {{ $task->delayed_days ?? 'N/A' }}
                    </td>
                    <td>{{ $task->delay_reason ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

