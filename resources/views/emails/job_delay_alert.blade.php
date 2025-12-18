<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .header { background-color: #dc3545; color: white; padding: 15px; border-radius: 5px 5px 0 0; }
        .content { padding: 20px; }
        .footer { background-color: #f8f9fa; padding: 15px; border-radius: 0 0 5px 5px; text-align: center; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table td { padding: 10px; border-bottom: 1px solid #ddd; }
        table td:first-child { font-weight: bold; width: 30%; }
        .alert-box { background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0; }
        .urgent-box { background-color: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>⚠️ Job Delay Alert</h2>
        </div>

        <div class="content">
            <p>Dear <strong>{{ $record->employee_name ?? 'Employee' }}</strong>,</p>
            
            <div class="urgent-box">
                <p><strong>🚨 URGENT - ACTION REQUIRED IMMEDIATELY 🚨</strong></p>
                <p><strong>Your assigned task has EXCEEDED the allocated time ({{ $record->standard_man_hours }} hours).</strong></p>
                <p style="margin-top: 10px; font-size: 16px;"><strong>Please complete it ASAP (As Soon As Possible)!</strong></p>
                <p style="margin-top: 10px;"><strong>This is a priority reminder - please attend to this task immediately.</strong></p>
            </div>

            <table>
                <tr>
                    <td>Ticket Number:</td>
                    <td><strong>{{ $record->ticket_number }}</strong></td>
                </tr>
                <tr>
                    <td>Project Name:</td>
                    <td>{{ $record->project_name }}</td>
                </tr>
                <tr>
                    <td>Job Card Date:</td>
                    <td>{{ \Carbon\Carbon::parse($record->job_card_date)->format('Y-m-d') }}</td>
                </tr>
                <tr>
                    <td>Allocated Time:</td>
                    <td><strong style="color: #dc3545; font-size: 16px;">{{ $record->standard_man_hours }} hours</strong></td>
                </tr>
                <tr>
                    <td>Start Time:</td>
                    <td>{{ $record->start_time ? \Carbon\Carbon::parse($record->start_time)->setTimezone('Asia/Dubai')->format('Y-m-d H:i') . ' (Dubai Time)' : 'N/A' }}</td>
                </tr>
                @if($record->end_time)
                <tr>
                    <td>End Time:</td>
                    <td>{{ \Carbon\Carbon::parse($record->end_time)->setTimezone('Asia/Dubai')->format('Y-m-d H:i') . ' (Dubai Time)' }}</td>
                </tr>
                <tr>
                    <td>Actual Duration:</td>
                    <td>{{ round(\Carbon\Carbon::parse($record->end_time)->setTimezone('Asia/Dubai')->diffInMinutes(\Carbon\Carbon::parse($record->start_time)->setTimezone('Asia/Dubai')) / 60, 2) }} hours</td>
                </tr>
                @elseif($record->start_time)
                @php
                    $now = \Carbon\Carbon::now('Asia/Dubai');
                    $startTime = \Carbon\Carbon::parse($record->start_time)->setTimezone('Asia/Dubai');
                    $currentHours = round($now->diffInMinutes($startTime) / 60, 2);
                    $currentPerformance = 0;
                    if ($record->standard_man_hours > 0 && $currentHours > 0) {
                        $currentPerformance = ($record->standard_man_hours / $currentHours) * 100;
                        $currentPerformance = max(0, min(100, round($currentPerformance, 2)));
                    }
                @endphp
                <tr>
                    <td>Current Duration:</td>
                    <td><strong style="color: #dc3545;">{{ $currentHours }} hours (in progress)</strong></td>
                </tr>
                <tr>
                    <td>Current Performance:</td>
                    <td><strong style="color: {{ $currentPerformance >= 100 ? '#28a745' : ($currentPerformance >= 50 ? '#ffc107' : '#dc3545') }};">{{ $currentPerformance }}%</strong></td>
                </tr>
                <tr>
                    <td>Allocated Time:</td>
                    <td><strong>{{ $record->standard_man_hours }} hours</strong></td>
                </tr>
                <tr>
                    <td>Hours Over Allocated Time:</td>
                    <td><strong style="color: #dc3545; font-size: 18px;">{{ round($currentHours - $record->standard_man_hours, 2) }} hours</strong></td>
                </tr>
                <tr>
                    <td>Time Remaining to Complete:</td>
                    <td><strong style="color: #dc3545;">You should have completed this task already. Please finish it immediately!</strong></td>
                </tr>
                @endif
                @if($record->delayed_days > 0)
                <tr>
                    <td>Delayed Days:</td>
                    <td><strong style="color: #dc3545;">{{ $record->delayed_days }} days</strong></td>
                </tr>
                @endif
                @if($record->performance_percent)
                <tr>
                    <td>Performance:</td>
                    <td>{{ $record->performance_percent }}%</td>
                </tr>
                @endif
                @if($record->delay_reason)
                <tr>
                    <td>Delay Reason:</td>
                    <td>{{ $record->delay_reason }}</td>
                </tr>
                @endif
            </table>

            <div class="alert-box">
                <p><strong>Action Required:</strong> Please complete this task immediately and update the system. If you need assistance or have questions, please contact your project manager right away.</p>
            </div>

            <p>Thank you for your attention to this matter.</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Tanseeq Investment - Asset Management System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
