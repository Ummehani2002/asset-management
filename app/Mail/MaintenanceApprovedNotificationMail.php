<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\MaintenanceApprovalRequest;

class MaintenanceApprovedNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $approvalRequest;
    public $approverName;

    public function __construct(MaintenanceApprovalRequest $approvalRequest, string $approverName)
    {
        $this->approvalRequest = $approvalRequest;
        $this->approverName = $approverName;
    }

    public function build()
    {
        $asset = $this->approvalRequest->asset;
        $serial = $asset ? ($asset->serial_number . ' (' . ($asset->asset_id ?? '') . ')') : 'Asset';

        return $this->subject('Maintenance Approved: ' . $serial)
            ->view('emails.maintenance_approved_notification');
    }
}
