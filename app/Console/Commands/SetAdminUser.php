<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:set-admin {email : The email or username of the user to set as admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set a user as admin by email or username';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $user = \App\Models\User::where('email', $email)
            ->orWhere('username', $email)
            ->first();
        
        if (!$user) {
            $this->error("User not found with email/username: {$email}");
            return 1;
        }
        
        $user->role = 'admin';
        $user->save();
        
        $this->info("✓ Successfully set user '{$user->name}' ({$user->email}) as admin!");
        $this->info("  User ID: {$user->id}");
        $this->info("  Username: {$user->username}");
        $this->info("  Role: {$user->role}");
        
        return 0;
    }
}
