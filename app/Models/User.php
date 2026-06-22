<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;

class User extends Authenticatable implements CanResetPassword, MustVerifyEmail
{
    use HasFactory, Notifiable, CanResetPasswordTrait;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'employee_id',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Entity names this user can manage (when linked to an employee who is asset manager).
     * Returns null = no restriction (admin or not an asset manager). Returns array = restrict to these entities.
     */
    public function getManagedEntityNames(): ?array
    {
        if ($this->role === 'admin' || !$this->employee_id) {
            return null;
        }
        $names = \App\Models\Entity::where('asset_manager_id', $this->employee_id)->pluck('name')->toArray();
        return $names ?: null;
    }

    public function isAssetManager(): bool
    {
        return $this->getManagedEntityNames() !== null;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
