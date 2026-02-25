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
            $this->line('Usage: php artisan mail:test --to=your@email.com');
            $this->line('(Production: run in Console with your email to verify mail is sent.)');
            if ($mailer === 'log') {
                $this->warn('Current mailer is "log" - no real email is sent. Set MAIL_MAILER=smtp and MAIL_* in .env for production.');
            }
            return 0;
        }

        try {
            Mail::raw(
                "This is a test email from your application.\n\nSent at: " . now()->toDateTimeString() . "\nFrom: {$fromName} <{$fromAddress}>\n\nIf you receive this, production mail is working.",
                function ($message) use ($to, $fromAddress, $fromName) {
                    $message->to($to)
                        ->subject('Test email - Asset Management');
                }
            );
            $this->info('Test email sent to: ' . $to);
            if ($mailer === 'log') {
                $this->warn('Mailer is "log". Check storage/logs/laravel.log for the message (no real email sent).');
            } else {
                $this->info('Check inbox and spam. From: ' . $fromName . ' <' . $fromAddress . '>');
            }
        } catch (\Throwable $e) {
            $this->error('Failed to send: ' . $e->getMessage());
            $this->line('Fix MAIL_* in production Environment. See MAIL_PRODUCTION.md for checklist.');
            return 1;
        }

        return 0;
    }
}
