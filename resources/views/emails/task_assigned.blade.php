<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .header { background-color: #1F2A44; color: white; padding: 15px; border-radius: 5px 5px 0 0; }
        .content { padding: 20px; }
        .footer { background-color: #f8f9fa; padding: 15px; border-radius: 0 0 5px 5px; text-align: center; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table td { padding: 10px; border-bottom: 1px solid #ddd; }
        table td:first-child { font-weight: bold; width: 30%; }
        .info-box { background-color: #e7f3ff; padding: 15px; border-left: 4px solid #1F2A44; margin: 15px 0; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #C6A87D; color: #1F2A44; text-decoration: none; border-radius: 5px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>New Task Assigned</h2>
        </div>

        <div class="content">
            <p>Dear <strong>{{ $record->employee_name ?? 'Employee' }}</strong>,</p>
            
            <p>A new task has been assigned to you. Please find the details below:</p>

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
                    <td><strong style="color: #1F2A44; font-size: 16px;">{{ $record->standard_man_hours }} hours</strong></td>
                </tr>
                <tr>
                    <td>Start Time:</td>
                    <td>{{ $record->start_time ? \Carbon\Carbon::parse($record->start_time)->format('Y-m-d H:i') : 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Status:</td>
                    <td><strong>{{ ucfirst($record->status ?? 'In Progress') }}</strong></td>
                </tr>
            </table>
            <div class="info-box">
                <p><strong>Important:</strong> This task has been assigned to you with an allocated time of <strong>{{ $record->standard_man_hours }} hours</strong>.</p>
                <p><strong>Please complete it within the allocated time ({{ $record->standard_man_hours }} hours).</strong></p>
                <p style="margin-top: 10px; color: #dc3545;"><strong>⚠️ If you do not complete it within {{ $record->standard_man_hours }} hours, you will receive reminder emails to complete it ASAP.</strong></p>
            </div>

            <p>If you have any questions, please contact your project manager.</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Tanseeq Investment - Asset Management System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

