<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMailCommand extends Command
{
    protected $signature = 'mail:test {--to= : Email address to send the test email to}';
    protected $description = 'Show mail config and optionally send a test email to verify the From address';

    public function handle()
    {
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name');
        $mailer = config('mail.default');

        $this->info('Current mail configuration:');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Mailer', $mailer],
                ['From address', $fromAddress],
                ['From name', $fromName],
            ]
        );

        $to = $this->option('to');
        if (!$to) {
            if ($this->confirm('Send a test email? (You can check the From header in the received email or in the log)', true)) {
                $to = $this->ask('Enter email address to send test to');
            }
        }

        if ($to) {
            try {
                Mail::raw(
                    "This is a test email from your application.\n\nSent at: " . now()->toDateTimeString() . "\nFrom: {$fromName} <{$fromAddress}>",
                    function ($message) use ($to, $fromAddress, $fromName) {
                        $message->to($to)
                            ->subject('Test email - check From address');
                    }
                );
                $this->info('Test email sent to: ' . $to);
                if ($mailer === 'log') {
                    $this->warn('Mailer is "log". Check storage/logs/laravel.log for the full message (search for "From:" to see the sender).');
                } else {
                    $this->info('Check your inbox (and spam). The From field should show: ' . $fromName . ' <' . $fromAddress . '>');
                }
            } catch (\Throwable $e) {
                $this->error('Failed to send: ' . $e->getMessage());
                return 1;
            }
        } else {
            $this->line('To test sending: php artisan mail:test --to=your@email.com');
            if ($mailer === 'log') {
                $this->warn('Current mailer is "log" - emails are written to storage/logs/laravel.log only.');
            }
        }

        return 0;
    }
}
