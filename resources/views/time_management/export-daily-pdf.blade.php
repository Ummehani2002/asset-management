<!DOCTYPE html>
<html>
<head>
    <title>Daily Work Report — {{ $summaryDate }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; color: #222; }
        h2 { color: #1F2A44; margin: 0 0 4px; }
        h3 { color: #1F2A44; margin: 18px 0 8px; font-size: 12px; }
        .meta { margin-bottom: 12px; }
        .meta p { margin: 2px 0; }
        .totals { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
        .totals td { border: 1px solid #ddd; padding: 8px; width: 25%; background: #f5f7fb; }
        .totals .label { font-size: 9px; color: #666; text-transform: uppercase; }
        .totals .value { font-size: 14px; font-weight: bold; color: #1F2A44; margin-top: 2px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.data th, table.data td { border: 1px solid #ddd; padding: 5px 6px; text-align: left; vertical-align: top; }
        table.data th { background-color: #1F2A44; color: white; }
        table.data tr:nth-child(even) { background-color: #f8f8f8; }
        .text-danger { color: #c62828; font-weight: bold; }
        .muted { color: #777; }
        .empty { text-align: center; padding: 16px; color: #777; }
    </style>
</head>
<body>
    <h2>Daily Work Report</h2>
    <div class="meta">
        <p><strong>Work Date:</strong> {{ \Carbon\Carbon::parse($summaryDate)->format('l, F j, Y') }}</p>
        <p><strong>Generated:</strong> {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <table class="totals">
        <tr>
            <td>
                <div class="label">Employees Worked</div>
                <div class="value">{{ $dailySummaryTotals['active_count'] ?? 0 }}</div>
            </td>
            <td>
                <div class="label">Team Hours</div>
                <div class="value">{{ \App\Models\TimeManagement::formatDuration($dailySummaryTotals['total_hours'] ?? 0) }}</div>
            </td>
            <td>
                <div class="label">Total Visits</div>
                <div class="value">{{ $visits->count() }}</div>
            </td>
            <td>
                <div class="label">Overtime</div>
                <div class="value">{{ \App\Models\TimeManagement::formatDuration($dailySummaryTotals['overtime_hours'] ?? 0) }}</div>
            </td>
        </tr>
    </table>

    <h3>Employee Summary</h3>
    <table class="data">
        <thead>
            <tr>
                <th>#</th>
                <th>Employee</th>
                <th>Visits</th>
                <th>Total Hours</th>
                <th>Overtime</th>
            </tr>
        </thead>
        <tbody>
            @php $summaryRow = 0; @endphp
            @forelse(collect($dailySummaries)->filter(fn ($s) => ($s['total_hours'] ?? 0) > 0) as $summary)
                @php $summaryRow++; @endphp
                <tr>
                    <td>{{ $summaryRow }}</td>
                    <td>{{ $summary['employee_name'] }}</td>
                    <td>{{ $summary['job_count'] ?? 0 }}</td>
                    <td>{{ \App\Models\TimeManagement::formatDuration($summary['total_hours'] ?? 0) }}</td>
                    <td class="{{ ($summary['overtime_hours'] ?? 0) > 0 ? 'text-danger' : '' }}">
                        {{ \App\Models\TimeManagement::formatDuration($summary['overtime_hours'] ?? 0) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="empty">No completed work hours logged for this date.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h3>Detailed Work Log — What Was Done</h3>
    <table class="data">
        <thead>
            <tr>
                <th>#</th>
                <th>Employee</th>
                <th>Ticket</th>
                <th>Category</th>
                <th>Task Description</th>
                <th>Site/Location</th>
                <th>Start</th>
                <th>End</th>
                <th>Hours</th>
                <th>OT</th>
                <th>Action/Resolution</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($visits as $index => $visit)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $visit->employee_name }}</td>
                    <td>{{ $visit->ticket_number }}</td>
                    <td>{{ $visit->category ?? 'End User Support' }}</td>
                    <td>{{ $visit->task_description }}</td>
                    <td>{{ $visit->site_location }}</td>
                    <td>{{ $visit->start_time ? $visit->start_time->format('H:i') : '-' }}</td>
                    <td>{{ $visit->end_time ? $visit->end_time->format('H:i') : '-' }}</td>
                    <td>{{ \App\Models\TimeManagement::formatDuration($visit->duration_hours ?? 0) }}</td>
                    <td class="{{ ($visit->overtime_hours ?? 0) > 0 ? 'text-danger' : '' }}">
                        {{ \App\Models\TimeManagement::formatDuration($visit->overtime_hours ?? 0) }}
                    </td>
                    <td>{{ $visit->action_taken }}</td>
                    <td>{{ ucfirst($visit->status === 'in_progress' ? 'pending' : ($visit->status ?? '-')) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="empty">No completed visits for this date.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
