<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TimeManagement;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\JobDelayAlertMail;
use Illuminate\Support\Facades\Log;

class CheckDelayedTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:check-delayed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for delayed tasks and send email alerts to employees';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for delayed tasks...');
        
        // Use Dubai timezone for all time calculations
        $now = Carbon::now('Asia/Dubai');
        $emailSentCount = 0;
        
        // Get all in-progress tasks
        $inProgressTasks = TimeManagement::where('status', 'in_progress')->get();
        
        $this->info("Found " . $inProgressTasks->count() . " in-progress task(s)");
        
        foreach ($inProgressTasks as $task) {
            $this->line("Checking task: {$task->ticket_number} (Standard hours: {$task->standard_man_hours})");
            $isDelayed = false;
            $delayReason = '';
            $exceededStandardHours = false;
            $actualHours = 0;
            
            // ONLY check if task is taking longer than allocated hours (hours-based tracking only)
            if ($task->start_time && $task->standard_man_hours > 0) {
                $startTime = Carbon::parse($task->start_time)->setTimezone('Asia/Dubai');
                $actualHours = $now->diffInMinutes($startTime) / 60;
                
                // If task has exceeded allocated hours, mark as delayed and send email
                if ($actualHours > $task->standard_man_hours) {
                    $isDelayed = true;
                    $exceededStandardHours = true;
                    
                    // Calculate current performance (for information)
                    $currentPerformance = ($task->standard_man_hours / $actualHours) * 100;
                    $currentPerformance = max(0, min(200, round($currentPerformance, 2)));
                    
                    $hoursOver = round($actualHours - $task->standard_man_hours, 2);
                    $delayReason = 'Task has exceeded allocated time. Allocated: ' . $task->standard_man_hours . ' hours, Current: ' . round($actualHours, 2) . ' hours, Over by: ' . $hoursOver . ' hours. Current Performance: ' . $currentPerformance . '%. Please complete it ASAP (As Soon As Possible)!';
                    
                    // Log for debugging
                    $this->line("Task {$task->ticket_number}: Exceeded allocated hours - Allocated: {$task->standard_man_hours} hrs, Actual: " . round($actualHours, 2) . " hrs, Over by: {$hoursOver} hrs, Performance: {$currentPerformance}%");
                } else {
                    $this->line("Task {$task->ticket_number}: Within allocated time - Allocated: {$task->standard_man_hours} hrs, Current: " . round($actualHours, 2) . " hrs");
                }
            } else {
                $this->line("Task {$task->ticket_number}: Cannot check - missing start_time or standard_man_hours");
            }
            
            // If delayed, check if we should send email
            if ($isDelayed) {
                $shouldSendEmail = false;
                
                // Send email if:
                // 1. Never sent before, OR
                // 2. If exceeded standard hours: send every 1 hour (more frequent for urgent)
                // 3. If only past deadline: send once per day
                if (!$task->last_delay_email_sent_at) {
                    $shouldSendEmail = true;
                    $this->line("Task {$task->ticket_number}: First delay email - will send");
                } else {
                    $lastEmailSent = Carbon::parse($task->last_delay_email_sent_at)->setTimezone('Asia/Dubai');
                    $hoursSinceLastEmail = $now->diffInHours($lastEmailSent);
                    
                    // For allocated hours exceeded, send reminder every 1 hour until completed
                    if ($hoursSinceLastEmail >= 1) {
                        $shouldSendEmail = true;
                        $this->line("Task {$task->ticket_number}: Allocated hours exceeded - {$hoursSinceLastEmail} hours since last email - will send reminder");
                    } else {
                        $this->line("Task {$task->ticket_number}: Allocated hours exceeded but only {$hoursSinceLastEmail} hours since last email - waiting for next check");
                    }
                }
                
                if ($shouldSendEmail) {
                    $employee = Employee::find($task->employee_id);
                    
                    if ($employee && $employee->email) {
                        try {
                            Mail::to($employee->email)->send(new JobDelayAlertMail($task));
                            
                            // Update last email sent timestamp
                            $task->last_delay_email_sent_at = $now;
                            $task->save();
                            
                            $emailSentCount++;
                            $this->info("Delay alert sent to {$employee->email} for task {$task->ticket_number}");
                            Log::info("Delay alert email sent to: {$employee->email} for task: {$task->ticket_number}");
                        } catch (\Exception $e) {
                            $this->error("Failed to send email to {$employee->email}: " . $e->getMessage());
                            Log::error("Failed to send delay alert email to {$employee->email}: " . $e->getMessage());
                        }
                    } else {
                        $this->warn("No email found for employee ID {$task->employee_id} (Task: {$task->ticket_number})");
                    }
                }
            }
        }
        
        $this->info("Completed. Sent {$emailSentCount} delay alert email(s).");
        return Command::SUCCESS;
    }
}
