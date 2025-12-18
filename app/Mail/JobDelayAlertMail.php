<?php

namespace App\Mail;

use App\Models\TimeManagement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class JobDelayAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $record;

    public function __construct(TimeManagement $record)
    {
        $this->record = $record;
    }

    public function build()
    {
        return $this->subject('🚨 URGENT: Task Exceeded Time - Complete ASAP - ' . $this->record->ticket_number)
                    ->view('emails.job_delay_alert');
    }
}
