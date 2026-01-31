<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isAdministration(): bool
    {
        return $this->role === 'administration';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    public function canRegisterPatients(): bool
    {
        return $this->isAdmin() || $this->isAdministration() || $this->isStaff();
    }

    public function canManageMedicalInfo(): bool
    {
        return $this->isAdmin() || $this->isAdministration() || $this->isStaff();
    }

    public function canViewReports(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user is staff or administration (same permissions).
     */
    public function isRegularUser(): bool
    {
        return $this->isStaff() || $this->isAdministration();
    }
}
