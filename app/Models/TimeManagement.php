<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TimeManagement extends Model
{
    use HasFactory;

    protected $table = 'time_managements'; 

    protected $fillable = [
        'ticket_number',
        'employee_id',
        'employee_name',
        'project_name',
        'job_card_date',
        'standard_man_hours',
        'start_time',
        'end_time',
        'duration_hours',
        'status',
        'delayed_days',
        'delay_reason',
        'performance_percent',
        'last_delay_email_sent_at',
    ];

    protected $casts = [
        'job_card_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'last_delay_email_sent_at' => 'datetime',
    ];

    public function getStartTimeFormattedAttribute()
    {
        return $this->start_time ? $this->start_time->format('Y-m-d H:i') : '-';
    }

    public function getEndTimeFormattedAttribute()
    {
        return $this->end_time ? $this->end_time->format('Y-m-d H:i') : '-';
    }
}
