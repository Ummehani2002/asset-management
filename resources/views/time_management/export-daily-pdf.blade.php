<!DOCTYPE html>
<html>
<head>
    <title>Daily Work Report — {{ $summaryDate }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #222; }
        h1 { color: #1F2A44; margin: 0 0 6px; font-size: 18px; }
        h2 { color: #1F2A44; margin: 16px 0 8px; font-size: 13px; border-bottom: 2px solid #1F2A44; padding-bottom: 4px; }
        .meta p { margin: 2px 0; }
        .totals { width: 100%; border-collapse: collapse; margin: 10px 0 14px; }
        .totals td { width: 25%; border: 1px solid #d7dce5; background: #f5f7fb; padding: 8px; }
        .totals .label { font-size: 9px; color: #666; text-transform: uppercase; }
        .totals .value { font-size: 15px; font-weight: bold; color: #1F2A44; margin-top: 2px; }
        .employee-block { margin: 0 0 16px; page-break-inside: avoid; border: 1px solid #d7dce5; }
        .employee-header { background: #1F2A44; color: #fff; padding: 8px 10px; }
        .employee-header .name { font-size: 13px; font-weight: bold; }
        .employee-header .stats { font-size: 10px; margin-top: 2px; }
        table.details { width: 100%; border-collapse: collapse; }
        table.details th { background: #eef1f6; color: #1F2A44; text-align: left; padding: 6px; border: 1px solid #d7dce5; font-size: 10px; }
        table.details td { padding: 7px 6px; border: 1px solid #d7dce5; vertical-align: top; font-size: 10px; }
        .work-details { font-weight: bold; color: #111; }
        .muted { color: #666; }
        .text-danger { color: #c62828; font-weight: bold; }
        .empty { text-align: center; padding: 18px; color: #777; border: 1px solid #ddd; }
        .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .summary-table th, .summary-table td { border: 1px solid #d7dce5; padding: 6px; text-align: left; }
        .summary-table th { background: #1F2A44; color: #fff; }
    </style>
</head>
<body>
    <h1>Daily Work Report</h1>
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
                <div class="value">{{ collect($groupedByEmployee)->sum('visit_count') }}</div>
            </td>
            <td>
                <div class="label">Overtime</div>
                <div class="value">{{ \App\Models\TimeManagement::formatDuration($dailySummaryTotals['overtime_hours'] ?? 0) }}</div>
            </td>
        </tr>
    </table>

    <h2>Who Worked Today</h2>
    <table class="summary-table">
        <thead>
            <tr>
                <th>Employee Name</th>
                <th>Visits</th>
                <th>Total Hours</th>
                <th>Overtime</th>
            </tr>
        </thead>
        <tbody>
            @forelse(collect($dailySummaries)->filter(fn ($s) => ($s['total_hours'] ?? 0) > 0) as $summary)
                <tr>
                    <td><strong>{{ $summary['employee_name'] }}</strong></td>
                    <td>{{ $summary['job_count'] ?? 0 }}</td>
                    <td>{{ \App\Models\TimeManagement::formatDuration($summary['total_hours'] ?? 0) }}</td>
                    <td class="{{ ($summary['overtime_hours'] ?? 0) > 0 ? 'text-danger' : '' }}">
                        {{ \App\Models\TimeManagement::formatDuration($summary['overtime_hours'] ?? 0) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="empty">No completed work hours logged for this date.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>Work Details by Employee</h2>

    @forelse($groupedByEmployee as $employeeGroup)
        <div class="employee-block">
            <div class="employee-header">
                <div class="name">{{ $employeeGroup['employee_name'] }}</div>
                <div class="stats">
                    {{ $employeeGroup['visit_count'] }} visit(s)
                    · Total {{ \App\Models\TimeManagement::formatDuration($employeeGroup['total_hours']) }}
                    @if(($employeeGroup['overtime_hours'] ?? 0) > 0)
                        · OT {{ \App\Models\TimeManagement::formatDuration($employeeGroup['overtime_hours']) }}
                    @endif
                </div>
            </div>
            <table class="details">
                <thead>
                    <tr>
                        <th style="width: 12%;">Ticket</th>
                        <th style="width: 34%;">Work Details</th>
                        <th style="width: 12%;">Site</th>
                        <th style="width: 8%;">Start</th>
                        <th style="width: 8%;">End</th>
                        <th style="width: 8%;">Hours</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 8%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employeeGroup['visits'] as $visit)
                        <tr>
                            <td><strong>{{ $visit['ticket_number'] }}</strong></td>
                            <td>
                                <div class="work-details">{{ $visit['task_description'] }}</div>
                                <div class="muted">{{ $visit['category'] }}</div>
                                @if(($visit['remarks'] ?? '-') !== '-')
                                    <div class="muted">Remarks: {{ $visit['remarks'] }}</div>
                                @endif
                            </td>
                            <td>{{ $visit['site_location'] }}</td>
                            <td>{{ $visit['start_time'] }}</td>
                            <td>{{ $visit['end_time'] }}</td>
                            <td>
                                {{ \App\Models\TimeManagement::formatDuration($visit['hours']) }}
                                @if(($visit['overtime_hours'] ?? 0) > 0)
                                    <div class="text-danger">OT {{ \App\Models\TimeManagement::formatDuration($visit['overtime_hours']) }}</div>
                                @endif
                            </td>
                            <td>{{ $visit['status'] }}</td>
                            <td>{{ $visit['action_taken'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div class="empty">No work logs found for this date. Ask team members to stop/complete their visits so hours appear in the report.</div>
    @endforelse
</body>
</html>
