<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Enums\UserStatus;

class User extends Authenticatable
{
    use Notifiable;

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
        return $this->roles->first()?->code->value ?? 'CS';
    }
}
