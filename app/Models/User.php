<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Enums\UserStatus;
use App\Enums\RoleCode;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasUuids, Notifiable;

    protected $table = 'users';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'auth_user_id',
        'email',
        'full_name',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'status'     => UserStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->auth_user_id)) {
                $user->auth_user_id = 'auth-' . \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    // ─── FilamentUser Interface ───────────────────────────

    public function canAccessPanel(Panel $panel): bool
    {
        // Allow all active users with specific internal roles
        return $this->status === UserStatus::ACTIVE
            && $this->hasAnyRole(['SUPER_ADMIN', 'MANAGER', 'CS', 'DESIGNER', 'PRODUCTION']);
    }

    public function getFilamentName(): string
    {
        return $this->full_name ?? 'User';
    }

    // ─── Relationships ────────────────────────────────────

    public function personnel(): HasOne
    {
        return $this->hasOne(Personnel::class, 'user_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
            ->withPivot('assigned_at');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'created_by_id');
    }

    public function notificationRecipients(): HasMany
    {
        return $this->hasMany(NotificationRecipient::class, 'user_id');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class, 'assignee_id');
    }

    // ─── Helpers ─────────────────────────────────────────

    /** Returns the primary role code string (e.g. 'CS', 'SUPER_ADMIN') */
    public function primaryRoleCode(): string
    {
        return $this->roles->first()?->code?->value ?? 'CS';
    }

    /** Check if user has a specific role */
    public function hasRole(string|RoleCode $role): bool
    {
        $roleValue = $role instanceof RoleCode ? $role->value : $role;
        return $this->roles->contains(fn ($r) => $r->code?->value === $roleValue);
    }

    /** Check if user has any of the given roles */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    /** Check if user is super admin or developer */
    public function isSuperAdmin(): bool
    {
        return $this->hasAnyRole(['SUPER_ADMIN', 'DEVELOPER']);
    }
}
