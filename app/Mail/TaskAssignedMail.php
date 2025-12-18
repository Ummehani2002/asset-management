<?php

namespace App\Mail;

use App\Models\TimeManagement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TaskAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $record;

    public function __construct(TimeManagement $record)
    {
        $this->record = $record;
    }

    public function build()
    {
        return $this->subject('New Task Assigned: ' . $this->record->ticket_number)
                    ->view('emails.task_assigned');
    }
}

