<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f5f5f5; }
        .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); color: white; padding: 25px 20px; text-align: center; }
        .header h2 { margin: 0; font-size: 22px; }
        .content { padding: 25px; }
        .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #e0e0e0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; background: #fafafa; border-radius: 6px; overflow: hidden; }
        table td { padding: 10px 15px; border-bottom: 1px solid #e0e0e0; }
        table tr:last-child td { border-bottom: none; }
        table td:first-child { font-weight: 600; width: 40%; color: #1F2A44; background: #f0f0f0; }
        .success-box { margin: 20px 0; padding: 15px; background: #E8F5E9; border-left: 4px solid #4CAF50; border-radius: 4px; }
        .btn { display: inline-block; padding: 12px 24px; margin: 10px 0; text-decoration: none; border-radius: 6px; font-weight: 600; color: white !important; background: #1F2A44; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Maintenance Request Approved</h2>
        </div>
        <div class="content">
            <p>Hello {{ $approvalRequest->requestedByUser->employee->name ?? $approvalRequest->requestedByUser->name ?? 'User' }},</p>
            
            <div class="success-box">
                <p style="margin: 0;"><strong>Good news!</strong> Your maintenance request has been approved by <strong>{{ $approverName }}</strong>.</p>
            </div>

            <p>You can now proceed to fill in the maintenance details for the following asset:</p>

            <table>
                <tr>
                    <td>Asset</td>
                    <td><strong>{{ $approvalRequest->asset->serial_number ?? 'N/A' }} ({{ $approvalRequest->asset->asset_id ?? 'N/A' }})</strong></td>
                </tr>
                <tr>
                    <td>Category</td>
                    <td>{{ $approvalRequest->asset->assetCategory->category_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Approved by</td>
                    <td>{{ $approverName }}</td>
                </tr>
                <tr>
                    <td>Approved at</td>
                    <td>{{ now()->format('d M Y, h:i A') }}</td>
                </tr>
            </table>

            <p><strong>Next Steps:</strong></p>
            <ol>
                <li>Go to <strong>System Maintenance</strong> in the Asset Management System</li>
                <li>Select the approved asset</li>
                <li>Fill in the maintenance details and submit</li>
            </ol>

            <p style="text-align: center;">
                <a href="{{ url('/asset-transactions/maintenance') }}" class="btn">Go to System Maintenance</a>
            </p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Tanseeq Asset Management System.</p>
        </div>
    </div>
</body>
</html>
