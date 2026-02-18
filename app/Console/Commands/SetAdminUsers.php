<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SetAdminUsers extends Command
{
    protected $signature = 'user:set-admins {emails* : Email or username of each user to set as admin}';
    protected $description = 'Set multiple users as admin by email or username';

    public function handle()
    {
        $emails = $this->argument('emails');
        if (empty($emails)) {
            $this->error('Provide at least one email or username. Example: php artisan user:set-admins user1@example.com user2 username3');
            return 1;
        }

        $updated = 0;
        $notFound = [];

        foreach ($emails as $email) {
            $email = trim($email);
            if ($email === '') {
                continue;
            }

            $user = User::whereRaw('LOWER(email) = ?', [strtolower($email)])
                ->orWhereRaw('LOWER(username) = ?', [strtolower($email)])
                ->first();

            if (!$user) {
                $notFound[] = $email;
                continue;
            }

            if ($user->role === 'admin') {
                $this->line("  {$user->name} ({$user->email}) is already admin.");
                continue;
            }

            $user->role = 'admin';
            $user->save();
            $updated++;
            $this->info("  ✓ Set as admin: {$user->name} ({$user->email})");
        }

        if ($updated > 0) {
            $this->info("");
            $this->info("Successfully set {$updated} user(s) as admin.");
        }
        if (!empty($notFound)) {
            $this->warn("Not found: " . implode(', ', $notFound));
        }

        return 0;
    }
}
