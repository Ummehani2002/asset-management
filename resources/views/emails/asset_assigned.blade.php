<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Assigned</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #2c3e50;
            background-color: #f0f2f5;
            margin: 0;
            padding: 20px;
        }
        .email-wrap {
            max-width: 620px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .header {
            background: linear-gradient(135deg, #1F2A44 0%, #2C3E66 100%);
            color: #ffffff;
            padding: 28px 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .header .sub {
            margin: 8px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 32px 28px;
        }
        .greeting {
            font-size: 17px;
            margin: 0 0 20px 0;
            color: #1F2A44;
        }
        .greeting strong {
            color: #1F2A44;
        }
        .intro {
            font-size: 15px;
            margin: 0 0 24px 0;
            color: #444;
        }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #1F2A44;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 12px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #C6A87D;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 24px 0;
            font-size: 14px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .details-table td {
            padding: 14px 18px;
            border-bottom: 1px solid #e8e8e8;
            vertical-align: top;
        }
        .details-table tr:last-child td {
            border-bottom: none;
        }
        .details-table td:first-child {
            font-weight: 600;
            width: 38%;
            min-width: 140px;
            color: #1F2A44;
            background: #f8f9fa;
        }
        .details-table td:last-child {
            color: #333;
            background: #fff;
        }
        .contact-it {
            background: linear-gradient(135deg, #E8F4FD 0%, #D1E9FA 100%);
            border: 1px solid #b8daff;
            border-left: 4px solid #1F2A44;
            border-radius: 8px;
            padding: 20px 22px;
            margin: 28px 0 0 0;
        }
        .contact-it .title {
            font-size: 15px;
            font-weight: 700;
            color: #1F2A44;
            margin: 0 0 8px 0;
        }
        .contact-it .text {
            font-size: 14px;
            margin: 0;
            color: #333;
            line-height: 1.65;
        }
        .info-box {
            background: #f0f7f0;
            border-left: 4px solid #4CAF50;
            border-radius: 6px;
            padding: 16px 20px;
            margin: 0 0 24px 0;
        }
        .info-box p { margin: 0; font-size: 14px; color: #2e5c2e; }
        .warning-box {
            background: #fff8e6;
            border-left: 4px solid #FF9800;
            border-radius: 6px;
            padding: 16px 20px;
            margin: 0 0 24px 0;
        }
        .warning-box p { margin: 0; font-size: 14px; color: #664d00; }
        .footer {
            background: #f8f9fa;
            padding: 20px 28px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #e9ecef;
        }
        .footer p { margin: 0; }
    </style>
</head>
<body>
    <div class="email-wrap">
        <div class="header">
            @php
                $transactionType = $transaction->transaction_type ?? 'assign';
                $isReassignment = $transactionType === 'assign' && $transaction->maintenance_notes !== null;
                $title = match($transactionType) {
                    'assign' => $isReassignment ? 'Asset Ready for Collection' : 'Asset Assigned to You',
                    'return' => 'Asset Return Notification',
                    'system_maintenance' => 'Asset Maintenance Notification',
                    default => 'Asset Transaction Notification',
                };
            @endphp
            <h1>{{ $title }}</h1>
            <p class="sub">Asset Management System</p>
        </div>

        <div class="content">
            <p class="greeting">Dear <strong>{{ $employee->name ?? $employee->entity_name ?? 'Employee' }}</strong>,</p>

            @if($transactionType === 'assign')
                @if($isReassignment)
                    <div class="info-box">
                        <p><strong>Your asset has completed maintenance and is ready for collection.</strong></p>
                    </div>
                @else
                    <p class="intro">This email is to inform you that <strong>the following asset has been assigned to you</strong>. Please find the details below.</p>
                @endif

                <p class="section-title">Asset Details</p>
                <table class="details-table">
                    <tr>
                        <td>Asset ID</td>
                        <td><strong>{{ $asset->asset_id ?? 'N/A' }}</strong></td>
                    </tr>
                    <tr>
                        <td>Category</td>
                        <td>{{ $asset->assetCategory->category_name ?? ($asset->category->category_name ?? 'N/A') }}</td>
                    </tr>
                    <tr>
                        <td>Brand</td>
                        <td>{{ $asset->brand->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Serial Number</td>
                        <td><strong>{{ $asset->serial_number ?? 'N/A' }}</strong></td>
                    </tr>
                    @if($asset->purchase_date ?? null)
                    <tr>
                        <td>Purchase Date</td>
                        <td>{{ \Carbon\Carbon::parse($asset->purchase_date)->format('F d, Y') }}</td>
                    </tr>
                    @endif
                    @if($asset->warranty_start ?? null)
                    <tr>
                        <td>Warranty Start</td>
                        <td>{{ \Carbon\Carbon::parse($asset->warranty_start)->format('F d, Y') }}</td>
                    </tr>
                    @endif
                    @if($asset->expiry_date ?? null)
                    <tr>
                        <td>Warranty End</td>
                        <td>{{ \Carbon\Carbon::parse($asset->expiry_date)->format('F d, Y') }}</td>
                    </tr>
                    @endif
                    @if($transaction->issue_date ?? null)
                    <tr>
                        <td>Issue Date</td>
                        <td><strong>{{ \Carbon\Carbon::parse($transaction->issue_date)->format('F d, Y') }}</strong></td>
                    </tr>
                    @endif
                    @if($transaction->location_id && ($transaction->location ?? null))
                    <tr>
                        <td>Location</td>
                        <td>{{ $transaction->location->location_name ?? 'N/A' }}</td>
                    </tr>
                    @endif
                </table>

                @if($isReassignment)
                    <div class="info-box">
                        <p><strong>Action required:</strong> Please collect your asset from the IT department. The asset has been reassigned to you and is ready for use.</p>
                    </div>
                @endif

                <div class="contact-it">
                    <p class="title">For any queries</p>
                    <p class="text">If you have any questions or concerns about this asset, please contact the <strong>IT Department</strong>. We will be happy to assist you.</p>
                    <p class="text" style="margin-top: 12px; font-size: 13px; color: #666;"><em>This email is auto-generated. Please do not reply to it.</em></p>
                </div>

            @elseif($transactionType === 'return')
                <p class="intro">The following asset has been returned. Details are below.</p>
                <p class="section-title">Asset Details</p>
                <table class="details-table">
                    <tr><td>Asset ID</td><td><strong>{{ $asset->asset_id ?? 'N/A' }}</strong></td></tr>
                    <tr><td>Category</td><td>{{ $asset->assetCategory->category_name ?? ($asset->category->category_name ?? 'N/A') }}</td></tr>
                    <tr><td>Brand</td><td>{{ $asset->brand->name ?? 'N/A' }}</td></tr>
                    <tr><td>Serial Number</td><td><strong>{{ $asset->serial_number ?? 'N/A' }}</strong></td></tr>
                    @if($transaction->return_date)
                    <tr><td>Return Date</td><td><strong>{{ \Carbon\Carbon::parse($transaction->return_date)->format('F d, Y') }}</strong></td></tr>
                    @endif
                    @if($transaction->remarks)
                    <tr><td>Remarks</td><td>{{ $transaction->remarks }}</td></tr>
                    @endif
                </table>
                <div class="info-box">
                    <p><strong>This asset has been successfully returned and is now available in the system.</strong></p>
                </div>
                <div class="contact-it">
                    <p class="title">For any queries</p>
                    <p class="text">If you have any questions, please contact the <strong>IT Department</strong>.</p>
                    <p class="text" style="margin-top: 12px; font-size: 13px; color: #666;"><em>This email is auto-generated. Please do not reply to it.</em></p>
                </div>

            @elseif($transactionType === 'system_maintenance')
                <div class="warning-box">
                    <p><strong>Your assigned asset has been sent for system maintenance.</strong></p>
                </div>
                <p class="section-title">Asset Details</p>
                <table class="details-table">
                    <tr><td>Asset ID</td><td><strong>{{ $asset->asset_id ?? 'N/A' }}</strong></td></tr>
                    <tr><td>Category</td><td>{{ $asset->assetCategory->category_name ?? ($asset->category->category_name ?? 'N/A') }}</td></tr>
                    <tr><td>Brand</td><td>{{ $asset->brand->name ?? 'N/A' }}</td></tr>
                    <tr><td>Serial Number</td><td><strong>{{ $asset->serial_number ?? 'N/A' }}</strong></td></tr>
                </table>
                @if($transaction->delivery_date ?? null)
                <p class="section-title">Maintenance</p>
                <table class="details-table">
                    <tr><td>Expected delivery</td><td><strong>{{ \Carbon\Carbon::parse($transaction->delivery_date)->format('F d, Y') }}</strong></td></tr>
                </table>
                @endif
                <div class="contact-it">
                    <p class="title">For any queries</p>
                    <p class="text">If you have any questions about this maintenance, please contact the <strong>IT Department</strong>.</p>
                    <p class="text" style="margin-top: 12px; font-size: 13px; color: #666;"><em>This email is auto-generated. Please do not reply to it.</em></p>
                </div>
            @endif
        </div>

        <div class="footer">
            <p><em>This is an auto-generated email. Please do not reply to this message.</em></p>
            <p style="margin-top: 10px;">&copy; {{ date('Y') }} Asset Management System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
