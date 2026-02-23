<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetUserPassword extends Command
{
    protected $signature = 'user:reset-password {email : User email or username} {password : New password}';

    protected $description = 'Reset a user\'s password by email or username (for production when self-service reset is unavailable)';

    public function handle()
    {
        $emailOrUsername = $this->argument('email');
        $password = $this->argument('password');

        $user = User::where('email', $emailOrUsername)
            ->orWhere('username', $emailOrUsername)
            ->first();

        if (!$user) {
            $this->error("User not found with email/username: {$emailOrUsername}");
            $this->line('List users: php artisan user:list');
            return 1;
        }

        if (strlen($password) < 6) {
            $this->error('Password must be at least 6 characters.');
            return 1;
        }

        $user->password = Hash::make($password);
        $user->save();

        $this->info("Password updated successfully for: {$user->name} ({$user->email})");
        $this->line("  Username: {$user->username}");
        return 0;
    }
}
