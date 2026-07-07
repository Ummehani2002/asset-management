<!DOCTYPE html>
<html>
<head>
    <title>Time Management Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; vertical-align: top; }
        th { background-color: #1F2A44; color: white; }
        tr:nth-child(even) { background-color: #f8f8f8; }
        h2 { color: #1F2A44; margin-bottom: 4px; }
        .text-danger { color: #c62828; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Time Management Team Report</h2>
    <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    <p><strong>Total Records:</strong> {{ count($tasks) }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Ticket</th>
                <th>Employee</th>
                <th>Category</th>
                <th>Task Description</th>
                <th>Site/Location</th>
                <th>Date</th>
                <th>Start</th>
                <th>End</th>
                <th>Time Spent</th>
                <th>Overtime</th>
                <th>Action/Resolution</th>
                <th>Status</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tasks as $index => $task)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $task->ticket_number }}</td>
                    <td>{{ $task->employee_name }}</td>
                    <td>{{ $task->category ?? 'End User Support' }}</td>
                    <td>{{ $task->task_description }}</td>
                    <td>{{ $task->site_location }}</td>
                    <td>{{ $task->job_card_date ? $task->job_card_date->format('Y-m-d') : '-' }}</td>
                    <td>{{ $task->start_time ? $task->start_time->format('Y-m-d H:i') : '-' }}</td>
                    <td>{{ $task->end_time ? $task->end_time->format('Y-m-d H:i') : '-' }}</td>
                    <td>{{ $task->duration_hours ?? 0 }} hrs</td>
                    <td class="{{ ($task->overtime_hours ?? 0) > 0 ? 'text-danger' : '' }}">{{ $task->overtime_hours ?? 0 }} hrs</td>
                    <td>{{ $task->action_taken }}</td>
                    <td>{{ ucfirst($task->status === 'in_progress' ? 'pending' : $task->status) }}</td>
                    <td>{{ $task->remarks }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
