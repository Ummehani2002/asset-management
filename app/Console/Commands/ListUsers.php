<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ListUsers extends Command
{
    protected $signature = 'user:list';
    protected $description = 'List all users (id, name, email, username, role) so you can use correct emails for user:set-admins';

    public function handle()
    {
        $users = User::orderBy('id')->get(['id', 'name', 'email', 'username', 'role']);
        if ($users->isEmpty()) {
            $this->warn('No users in the database. Create users via Register or the Users page first.');
            return 0;
        }
        $this->table(
            ['ID', 'Name', 'Email', 'Username', 'Role'],
            $users->map(fn ($u) => [$u->id, $u->name ?? '-', $u->email ?? '-', $u->username ?? '-', $u->role ?? '-'])
        );
        $this->line('');
        $this->info('To set admins, use the email or username from the table:');
        $this->line('  php artisan user:set-admins "email_or_username" "second_user" ...');
        return 0;
    }
}
